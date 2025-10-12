<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Facades\Cfg;
use App\Jobs\ProcessUploadedVideo;
use App\Models\User;
use App\Models\Video;
use App\Services\IngestScanner;
use Tests\DatabaseTestCase;

final class ProcessUploadedVideoTest extends DatabaseTestCase
{
    public function testHandleProcessesFileAndCreatesClip(): void
    {
        Cfg::set('default_file_system', 'testdisk', 'default');

        $path = storage_path('app/test.mp4');
        file_put_contents($path, 'video-data');
        $hash = hash_file('sha256', $path);

        $video = Video::factory()->create([
            'hash' => $hash,
            'ext' => 'mp4',
            'bytes' => filesize($path),
            'path' => 'videos/'.$hash.'.mp4',
            'disk' => 'local',
        ]);

        $user = User::factory()->admin()->create();

        $scanner = app(IngestScanner::class);

        $job = new ProcessUploadedVideo(
            user: $user,
            path: $path,
            originalName: 'clip.mp4',
            ext: 'mp4',
            start: 5,
            end: 12,
            submittedBy: 'alice',
            note: 'note here',
            bundleKey: 'bundleA',
            role: 'driver',
        );

        $job->handle($scanner);

        $this->assertFileDoesNotExist($path);

        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'start_sec' => 5,
            'end_sec' => 12,
            'submitted_by' => 'alice',
            'note' => 'note here',
            'bundle_key' => 'bundleA',
            'role' => 'driver',
        ]);


        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Video::class,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'description' => 'uploaded a video',
        ]);

        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
