<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Cleanup;

use App\Application\Cleanup\PurgeDeletedVideosUseCase;
use App\Constants\Config\DefaultConfigEntry;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Assignment;
use App\Models\Download;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class PurgeDeletedVideosUseCaseTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Cfg::set(DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS, 2, 'default', 'int');
    }

    public function testRemovesTheFilesButKeepsTheVideoWithItsHistory(): void
    {
        $video = $this->deletedVideo(weeksAgo: 3);
        $assignment = Assignment::factory()->forVideo($video)->create(['status' => StatusEnum::PICKEDUP->value]);
        $download = Download::factory()->forAssignment($assignment)->create();

        $result = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(1, $result->purged);
        self::assertSame(0, $result->failed);
        Storage::disk('local')->assertMissing($video->path);
        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        self::assertSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($video->getKey())->processing_status);
        $this->assertDatabaseHas('assignments', ['id' => $assignment->getKey()]);
        $this->assertDatabaseHas('downloads', ['id' => $download->getKey()]);
    }

    public function testKeepsVideosStillWithinTheRetentionPeriod(): void
    {
        $video = $this->deletedVideo(weeksAgo: 1);

        self::assertSame(0, app(PurgeDeletedVideosUseCase::class)->handle()->purged);

        Storage::disk('local')->assertExists($video->path);
        self::assertNotSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($video->getKey())->processing_status);
    }

    public function testNeverTouchesVideosThatAreNotDeleted(): void
    {
        $video = Video::factory()->create(['disk' => 'local', 'created_at' => now()->subYear()]);
        Storage::disk('local')->put($video->path, 'video-bytes');

        app(PurgeDeletedVideosUseCase::class)->handle();

        Storage::disk('local')->assertExists($video->path);
        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
    }

    public function testAPurgedVideoIsNotPickedUpAgain(): void
    {
        $this->deletedVideo(weeksAgo: 3);
        app(PurgeDeletedVideosUseCase::class)->handle();

        $second = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(0, $second->purged);
        self::assertSame(0, $second->failed);
    }

    public function testFollowsTheConfiguredRetentionPeriod(): void
    {
        Cfg::set(DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS, 5, 'default', 'int');
        $kept = $this->deletedVideo(weeksAgo: 4);
        $purged = $this->deletedVideo(weeksAgo: 6);

        app(PurgeDeletedVideosUseCase::class)->handle();

        Storage::disk('local')->assertExists($kept->path);
        Storage::disk('local')->assertMissing($purged->path);
    }

    public function testAVideoWhoseFileCannotBeRemovedIsRetriedWhileTheOthersArePurged(): void
    {
        $stuck = $this->deletedVideo(weeksAgo: 3, disk: 'broken');
        $purged = $this->deletedVideo(weeksAgo: 3);

        $result = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(1, $result->purged);
        self::assertSame(1, $result->failed);
        self::assertNotSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($stuck->getKey())->processing_status);
        Storage::disk('local')->assertMissing($purged->path);
        self::assertSame(1, app(PurgeDeletedVideosUseCase::class)->handle()->failed);
    }

    public function testAFailedStatusUpdateCountsAsFailedNotPurged(): void
    {
        $video = $this->deletedVideo(weeksAgo: 3);
        $this->mock(\App\Repository\VideoRepository::class, function ($mock) use ($video) {
            $mock->shouldReceive('lazyDeletedBefore')->once()->andReturn(\Illuminate\Support\LazyCollection::make([$video]));
            $mock->shouldReceive('updateProcessingStatus')->once()->andReturn(false);
        });

        $result = app(PurgeDeletedVideosUseCase::class)->handle();

        self::assertSame(0, $result->purged);
        self::assertSame(1, $result->failed);
    }

    public function testDryRunChangesNothing(): void
    {
        $video = $this->deletedVideo(weeksAgo: 3);

        $result = app(PurgeDeletedVideosUseCase::class)->handle(dryRun: true);

        self::assertSame(1, $result->purged);
        Storage::disk('local')->assertExists($video->path);
        self::assertNotSame(ProcessingStatusEnum::Deleted, Video::withTrashed()->find($video->getKey())->processing_status);
    }

    private function deletedVideo(int $weeksAgo, string $disk = 'local'): Video
    {
        $video = Video::factory()->create(['disk' => $disk]);
        if ($disk === 'local') {
            Storage::disk('local')->put($video->path, 'video-bytes');
        }
        $video->delete();
        $video->forceFill(['deleted_at' => now()->subWeeks($weeksAgo)])->saveQuietly();

        return $video;
    }
}
