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
use App\Models\Download;
use App\Models\Video;
use App\Services\AssignmentDistributor;
use RuntimeException;
use Tests\DatabaseTestCase;

/**
 * A returned offer hands the video back to the distribution, even when the channel had already
 * downloaded it. The channel that returned it never gets the same video again.
 */
final class ReturnedOfferDistributionTest extends DatabaseTestCase
{
    private AssignmentDistributor $distributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->distributor = app(AssignmentDistributor::class);
        Channel::query()->delete();
        Cfg::set(DefaultConfigEntry::DISTRIBUTION_ROUNDS, 2, 'default', 'int');
    }

    public function testAVideoReturnedAfterADownloadReachesTheNextChannel(): void
    {
        $returning = $this->channel('Returning');
        $next = $this->channel('Next');
        $video = $this->video();
        $returned = $this->returnedOffer($video, $returning);
        Download::factory()->forAssignment($returned)->create();

        $this->distribute();

        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $next->getKey(),
            'status' => StatusEnum::QUEUED->value,
        ]);
    }

    public function testTheReturningChannelDoesNotGetItAgain(): void
    {
        $returning = $this->channel('Returning');
        $this->channel('Next');
        $video = $this->video();
        $returned = $this->returnedOffer($video, $returning);
        Download::factory()->forAssignment($returned)->create();

        $this->distribute();

        self::assertSame(StatusEnum::REJECTED->value, $returned->refresh()->status);
        self::assertSame(1, Assignment::query()
            ->where('video_id', $video->getKey())
            ->where('channel_id', $returning->getKey())
            ->count());
    }

    public function testAVideoAChannelStillHoldsIsNotOfferedToAnyoneElse(): void
    {
        $holding = $this->channel('Holding');
        $next = $this->channel('Next');
        $video = $this->video();
        $held = Assignment::factory()->forVideo($video)->forChannel($holding)->create([
            'status' => StatusEnum::PICKEDUP->value,
            'expires_at' => now()->subDay(),
        ]);
        Download::factory()->forAssignment($held)->create();

        $this->distribute();

        $this->assertDatabaseMissing('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $next->getKey(),
        ]);
    }

    /**
     * Run the distribution, treating an empty pool as a run without any assignment.
     */
    private function distribute(): void
    {
        try {
            $this->distributor->distribute();
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== 'nothing to assign') {
                throw $exception;
            }
        }
    }

    private function channel(string $name): Channel
    {
        return Channel::factory()->create(['name' => $name, 'weight' => 1, 'weekly_quota' => 10]);
    }

    private function video(): Video
    {
        $video = Video::factory()->create(['processing_status' => ProcessingStatusEnum::Completed]);
        Clip::factory()->for($video, 'video')->create();

        return $video;
    }

    private function returnedOffer(Video $video, Channel $channel): Assignment
    {
        return Assignment::factory()->forVideo($video)->forChannel($channel)->create([
            'status' => StatusEnum::REJECTED->value,
            'expires_at' => now()->subDay(),
        ]);
    }
}
