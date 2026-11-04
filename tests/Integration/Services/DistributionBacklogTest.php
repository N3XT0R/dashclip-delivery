<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\ProcessingStatusEnum;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Services\AssignmentDistributor;
use Tests\DatabaseTestCase;

/**
 * A run tells how much work it left behind: the videos it had no place for any more and the
 * uploaders it could not serve at all. Without those numbers a run that placed one video looks
 * the same as one that had nothing to do.
 */
final class DistributionBacklogTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Channel::query()->delete();
    }

    public function testVideosLeftOverWhenThePlacesRunOutAreCountedAsWaiting(): void
    {
        $this->channel('Only', 1);
        $this->video();
        $this->video();
        $this->video();

        $result = app(AssignmentDistributor::class)->distribute();

        self::assertSame(1, $result['assigned']);
        self::assertSame(2, $result['waiting']);
    }

    public function testTheBatchKeepsTheNumbersOfTheRun(): void
    {
        $this->channel('Only', 1);
        $this->video();
        $this->video();

        app(AssignmentDistributor::class)->distribute();

        $stats = Batch::query()->latest('id')->first()?->stats;
        self::assertSame(1, $stats['assigned'] ?? null);
        self::assertSame(1, $stats['waiting'] ?? null);
        self::assertSame(0, $stats['failed'] ?? null);
    }

    public function testAnUploaderWithoutAnyReachableChannelIsCountedAsFailed(): void
    {
        $this->channel('Open', 5);
        $paused = $this->channel('Paused', 5);
        $paused->update(['is_video_reception_paused' => true]);
        $team = Team::factory()->forUser(User::factory()->create())->create();
        $team->channelAssignments()->create(['channel_id' => $paused->getKey(), 'quota' => 5]);
        $this->video($team);

        $result = app(AssignmentDistributor::class)->distribute();

        self::assertSame(1, $result['failed']);
        self::assertSame(0, $result['assigned']);
    }

    private function channel(string $name, int $quota): Channel
    {
        return Channel::factory()->create(['name' => $name, 'weight' => 1, 'weekly_quota' => $quota]);
    }

    private function video(?Team $team = null): Video
    {
        $video = $team instanceof Team
            ? Video::factory()->for($team, 'team')->create(['processing_status' => ProcessingStatusEnum::Completed])
            : Video::factory()->create(['processing_status' => ProcessingStatusEnum::Completed]);
        Clip::factory()->for($video, 'video')->create();

        return $video;
    }
}
