<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use App\Services\AssignmentDistributor;
use Carbon\CarbonInterface;
use Tests\DatabaseTestCase;

/**
 * When a run has fewer free places than waiting videos, the places go to the videos that have come
 * off worst so far: the ones that reached the fewest channels, and among those the ones waiting the
 * longest. Otherwise the lowest video ids would take every place, run after run.
 */
final class DistributionOrderTest extends DatabaseTestCase
{
    private AssignmentDistributor $distributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->distributor = app(AssignmentDistributor::class);
        Channel::query()->delete();
        Cfg::set(DefaultConfigEntry::DISTRIBUTION_ROUNDS, 2, 'default', 'int');
    }

    public function testTheLongestWaitingVideoTakesTheLastFreePlace(): void
    {
        $served = $this->channel('Served', 0);
        $free = $this->channel('Free', 1);
        $recent = $this->video();
        $waiting = $this->video();
        $this->expiredOffer($recent, $served, now()->subDay());
        $this->expiredOffer($waiting, $served, now()->subWeeks(3));

        $this->distributor->distribute();

        $this->assertDatabaseHas('assignments', [
            'video_id' => $waiting->getKey(),
            'channel_id' => $free->getKey(),
        ]);
        $this->assertDatabaseMissing('assignments', [
            'video_id' => $recent->getKey(),
            'channel_id' => $free->getKey(),
        ]);
    }

    public function testAVideoThatNeverReachedTheChannelComesBeforeARepeat(): void
    {
        $first = $this->channel('First', 0);
        $free = $this->channel('Free', 1);
        $third = $this->channel('Third', 0);
        $everywhere = $this->video();
        $newcomer = $this->video();
        foreach ([$first, $free, $third] as $channel) {
            $this->expiredOffer($everywhere, $channel, now()->subWeeks(3));
        }
        $this->expiredOffer($newcomer, $first, now()->subDay());

        $this->distributor->distribute();

        $this->assertDatabaseHas('assignments', [
            'video_id' => $newcomer->getKey(),
            'channel_id' => $free->getKey(),
            'offer_round' => 1,
        ]);
        self::assertSame(StatusEnum::EXPIRED->value, Assignment::query()
            ->where('video_id', $everywhere->getKey())
            ->where('channel_id', $free->getKey())
            ->value('status'));
    }

    private function channel(string $name, int $quota): Channel
    {
        return Channel::factory()->create(['name' => $name, 'weight' => 1, 'weekly_quota' => $quota]);
    }

    private function video(): Video
    {
        $video = Video::factory()->create(['processing_status' => ProcessingStatusEnum::Completed]);
        Clip::factory()->for($video, 'video')->create();

        return $video;
    }

    /**
     * An offer sent a week before it ran out, so it does not eat into this week's quota.
     */
    private function expiredOffer(Video $video, Channel $channel, CarbonInterface $expiredAt): Assignment
    {
        $offer = Assignment::factory()->forVideo($video)->forChannel($channel)->create([
            'status' => StatusEnum::EXPIRED->value,
            'expires_at' => $expiredAt,
        ]);
        $offer->forceFill(['created_at' => $expiredAt->copy()->subWeek()])->save();

        return $offer;
    }
}
