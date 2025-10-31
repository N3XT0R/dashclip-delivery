<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\DTO\FileInfoDto;
use App\Enum\Ingest\IngestResult;
use App\Facades\DynamicStorage;
use App\Jobs\ProcessUploadedVideo;
use App\Models\Activity;
use App\Models\User;
use App\Models\Video;
use App\Services\Ingest\IngestScanner;
use Mockery;
use Tests\DatabaseTestCase;

class ProcessUploadedVideoTest extends DatabaseTestCase
{

    public function testJobWritesActivityAndCreatesClip(): void
    {
        \Storage::fake('tmp');
        // Arrange
        $user = User::factory()->create();
        $fileInfo = new FileInfoDto('standalone.mp4', 'standalone.mp4', 'mp4');
        $disk = DynamicStorage::fromPath(base_path('tests/Fixtures/Inbox/Videos'));
        $hash = DynamicStorage::getHashForFilePath($disk, $fileInfo->path);

        $video = Video::factory()->create([
            'hash' => $hash,
            'original_name' => 'standalone.mp4',
        ]);

        // Mock IngestScanner, so processFile does nothing
        $scannerMock = Mockery::mock(IngestScanner::class);
        $scannerMock->shouldReceive('processFile')
            ->withArgs(function ($inboxDisk, $file, $diskName) {
                return true;
            })
            ->andReturn(IngestResult::NEW);
        $this->app->instance(IngestScanner::class, $scannerMock);

        // Act
        $job = new ProcessUploadedVideo(
            $user,
            $fileInfo,
            'tmp',
            'local',
            0,
            10,
            $user->display_name,
            'test note',
            'bundle-1',
            'main'
        );

        $job->handle($scannerMock);

        // Assert
        $activity = Activity::where('subject_id', $video->id)
            ->where('log_name', 'default')
            ->first();

        $this->assertNotNull($activity, 'Expected an activity entry for the video.');
        $this->assertSame('uploaded a video', $activity->description);
        $this->assertSame($user->getKey(), $activity->causer_id);

        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'start_sec' => 0,
            'end_sec' => 10,
            'submitted_by' => $user->display_name,
            'user_id' => $user->getKey(),
            'note' => 'test note',
            'bundle_key' => 'bundle-1',
            'role' => 'main',
        ]);
    }
}
