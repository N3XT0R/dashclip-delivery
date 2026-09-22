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
use Tests\DatabaseTestCase;

/**
 * A channel may receive the same video again once its offer expired, but only after every other
 * reachable channel had it and only as often as the configured number of rounds allows.
 */
final class DistributionRoundsTest extends DatabaseTestCase
{
    private AssignmentDistributor $distributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->distributor = app(AssignmentDistributor::class);
        Channel::query()->delete();
    }

    public function testChannelsWithoutAnOfferComeBeforeARepeat(): void
    {
        $this->rounds(2);
        $first = $this->channel('First');
        $second = $this->channel('Second');
        $video = $this->video();
        $this->expiredOffer($video, $first);

        $this->distributor->distribute();

        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $second->getKey(),
            'status' => StatusEnum::QUEUED->value,
            'offer_round' => 1,
        ]);
        $this->assertDatabaseHas('assignments', [
            'video_id' => $video->getKey(),
            'channel_id' => $first->getKey(),
            'status' => StatusEnum::EXPIRED->value,
            'offer_round' => 1,
        ]);
    }

    public function testAnExpiredOfferIsRepeatedOnceEveryChannelHadTheVideo(): void
    {
        $this->rounds(2);
        $only = $this->channel('Only');
        $video = $this->video();
        $expired = $this->expiredOffer($video, $only);

        $result = $this->distributor->distribute();

        $this->assertSame(1, $result['assigned']);
        $expired->refresh();
        self::assertSame(StatusEnum::QUEUED->value, $expired->status);
        self::assertSame(2, (int)$expired->offer_round);
        self::assertNull($expired->expires_at);
        self::assertSame(1, Assignment::query()->where('video_id', $video->getKey())->count());
    }

    public function testASecondRoundIsTheLastOneWithTwoRoundsConfigured(): void
    {
        $this->rounds(2);
        $only = $this->channel('Only');
        $video = $this->video();
        $offer = $this->expiredOffer($video, $only);
        $offer->forceFill(['offer_round' => 2])->save();

        $result = $this->distributor->distribute();

        $this->assertSame(0, $result['assigned']);
        self::assertSame(StatusEnum::EXPIRED->value, $offer->refresh()->status);
    }

    public function testWithOneRoundNothingIsRepeated(): void
    {
        $this->rounds(1);
        $only = $this->channel('Only');
        $video = $this->video();
        $offer = $this->expiredOffer($video, $only);

        $result = $this->distributor->distribute();

        $this->assertSame(0, $result['assigned']);
        self::assertSame(StatusEnum::EXPIRED->value, $offer->refresh()->status);
        self::assertSame(1, (int)$offer->offer_round);
    }

    public function testAReturnedOfferIsNeverRepeated(): void
    {
        $this->rounds(3);
        $only = $this->channel('Only');
        $video = $this->video();
        $returned = Assignment::factory()->forVideo($video)->forChannel($only)->create([
            'status' => StatusEnum::REJECTED->value,
            'expires_at' => now()->subDay(),
        ]);

        $result = $this->distributor->distribute();

        $this->assertSame(0, $result['assigned']);
        self::assertSame(StatusEnum::REJECTED->value, $returned->refresh()->status);
    }

    public function testARepeatedOfferBelongsToTheCurrentBatch(): void
    {
        $this->rounds(2);
        $only = $this->channel('Only');
        $video = $this->video();
        $offer = $this->expiredOffer($video, $only);
        $formerBatchId = $offer->batch_id;

        $this->distributor->distribute();

        self::assertNotSame($formerBatchId, $offer->refresh()->batch_id);
    }

    private function rounds(int $rounds): void
    {
        Cfg::set(DefaultConfigEntry::DISTRIBUTION_ROUNDS, $rounds, 'default', 'int');
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

    private function expiredOffer(Video $video, Channel $channel): Assignment
    {
        return Assignment::factory()->forVideo($video)->forChannel($channel)->withBatch()->create([
            'status' => StatusEnum::EXPIRED->value,
            'expires_at' => now()->subDay(),
        ]);
    }
}
