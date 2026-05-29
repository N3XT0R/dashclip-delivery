<?php

declare(strict_types=1);

namespace Tests\Integration\Commands\VideoProcessing;

use App\Enum\ProcessingStatusEnum;
use App\Events\Video\VideoQueuedForIngest;
use App\Models\Video;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

/**
 * Tests AbstractRequeueVideosCommand behavior via concrete implementations.
 */
final class RequeueVideosCommandsTest extends DatabaseTestCase
{
    public function testDispatchesIngestEventWhenFileExistsOnDisk(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('local');
        Storage::disk('local')->put('videos/stale.mp4', 'content');

        Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/stale.mp4',
            'processing_status' => ProcessingStatusEnum::Failed,
            'updated_at' => now()->subHours(2),
        ]);

        $this->artisan('video-processing:requeue-failed')->assertExitCode(0);

        Event::assertDispatched(VideoQueuedForIngest::class, 1);
    }

    public function testDeletesVideoWhenFileIsMissingFromDisk(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('local');

        $video = Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/missing.mp4',
            'processing_status' => ProcessingStatusEnum::Failed,
            'updated_at' => now()->subHours(2),
        ]);

        $this->artisan('video-processing:requeue-failed')->assertExitCode(0);

        Event::assertNotDispatched(VideoQueuedForIngest::class);
        $this->assertNull(Video::query()->find($video->id));
    }

    public function testReturnsSuccessWithNoVideosToProcess(): void
    {
        $this->artisan('video-processing:requeue-failed')->assertExitCode(0);
        $this->artisan('video-processing:requeue-never-ran')->assertExitCode(0);
        $this->artisan('video-processing:requeue-stale-running')->assertExitCode(0);
        $this->artisan('video-processing:requeue-missing-ingest-steps')->assertExitCode(0);
    }

    public function testRequeueNeverRanDispatchesEventForOldPendingVideo(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('local');
        Storage::disk('local')->put('videos/pending.mp4', 'content');

        Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/pending.mp4',
            'processing_status' => ProcessingStatusEnum::Pending,
            'updated_at' => now()->subHours(7),
        ]);

        $this->artisan('video-processing:requeue-never-ran')->assertExitCode(0);

        Event::assertDispatched(VideoQueuedForIngest::class, 1);
    }

    public function testRequeueStaleRunningDispatchesEventForStuckVideo(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('local');
        Storage::disk('local')->put('videos/running.mp4', 'content');

        Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/running.mp4',
            'processing_status' => ProcessingStatusEnum::Running,
            'updated_at' => now()->subHours(3),
        ]);

        $this->artisan('video-processing:requeue-stale-running')->assertExitCode(0);

        Event::assertDispatched(VideoQueuedForIngest::class, 1);
    }
}
