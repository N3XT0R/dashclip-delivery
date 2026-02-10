<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\DTO\FileInfoDto;
use App\Enum\Ingest\IngestResult;
use App\Facades\DynamicStorage;
use App\Jobs\ProcessUploadedVideo;
use App\Models\Activity;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Services\Ingest\IngestScanner;
use Mockery;
use Tests\DatabaseTestCase;
use Tests\Testing\Traits\CopyDiskTrait;

class ProcessUploadedVideoTest extends DatabaseTestCase
{
    use CopyDiskTrait;

    public function testJobWritesActivityAndCreatesClip(): void
    {
        \Storage::fake('tmp');
        \Storage::fake('local');
        // Arrange
        $user = User::factory()->create();
        $fileInfo = new FileInfoDto('standalone.mp4', 'standalone.mp4', 'mp4');
        $disk = DynamicStorage::fromPath(base_path('tests/Fixtures/Inbox/Videos'));
        $hash = DynamicStorage::getHashForFilePath($disk, $fileInfo->path);
        $this->copyDisk($disk, \Storage::disk('local'));

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
        $activity = Activity::all()->last();

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

    public function testJobAssignsTeamToVideoWhenProvided(): void
    {
        \Storage::fake('tmp');
        \Storage::fake('local');

        // Arrange
        User::flushEventListeners();
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $fileInfo = new FileInfoDto('standalone.mp4', 'standalone.mp4', 'mp4');
        $disk = DynamicStorage::fromPath(base_path('tests/Fixtures/Inbox/Videos'));
        $hash = DynamicStorage::getHashForFilePath($disk, $fileInfo->path);
        $this->copyDisk($disk, \Storage::disk('local'));

        $video = Video::factory()->create([
            'hash' => $hash,
            'original_name' => 'standalone.mp4',
            'team_id' => null,
        ]);

        $scannerMock = Mockery::mock(IngestScanner::class);
        $scannerMock->shouldReceive('processFile')
            ->withArgs(function ($inboxDisk, $file, $diskName, $userArg) use ($user) {
                return $userArg->is($user);
            })
            ->andReturn(IngestResult::NEW);

        $this->app->instance(IngestScanner::class, $scannerMock);

        $job = new ProcessUploadedVideo(
            user: $user,
            fileInfoDto: $fileInfo,
            targetDisk: 'tmp',
            sourceDisk: 'local',
            start: 0,
            end: 15,
            submittedBy: $user->display_name,
            note: 'team test',
            bundleKey: 'bundle-77',
            role: 'main',
            team: $team,
        );

        $job->handle($scannerMock);

        $video->refresh();

        $this->assertSame(
            $team->getKey(),
            $video->getKey(),
            'Expected the Video to be assigned to the provided team.'
        );

        $this->assertDatabaseHas('clips', [
            'video_id' => $video->id,
            'start_sec' => 0,
            'end_sec' => 15,
            'submitted_by' => $user->display_name,
            'note' => 'team test',
            'bundle_key' => 'bundle-77',
            'role' => 'main',
            'user_id' => $user->id,
        ]);

        $this->assertNotNull(
            Activity::where('subject_id', $video->id)
                ->where('log_name', 'default')
                ->first(),
            'Expected activity entry for team-assigned video upload.'
        );
    }

    public function testJobPassesInboxDiskNameToScanner(): void
    {
        \Storage::fake('tmp');
        \Storage::fake('uploads');

        User::flushEventListeners();
        $user = User::factory()->create();

        $fileInfo = new FileInfoDto('standalone.mp4', 'standalone.mp4', 'mp4');
        $disk = DynamicStorage::fromPath(base_path('tests/Fixtures/Inbox/Videos'));
        $hash = DynamicStorage::getHashForFilePath($disk, $fileInfo->path);
        $this->copyDisk($disk, \Storage::disk('uploads'));

        $job = new ProcessUploadedVideo(
            user: $user,
            fileInfoDto: $fileInfo,
            targetDisk: 'tmp',
            sourceDisk: 'uploads',
            start: 0,
            end: 10,
            submittedBy: $user->display_name,
            note: 'inbox name test',
            bundleKey: 'bundle-inbox',
            role: 'main',
        );

        $job->handle($this->app->make(IngestScanner::class));

        $this->assertDatabaseHas('videos', [
            'disk' => 'tmp',
            'hash' => $hash,
            'original_name' => 'standalone.mp4',
        ]);

        $this->assertDatabaseHas('clips', [
            'start_sec' => 0,
            'end_sec' => 10,
            'submitted_by' => $user->display_name,
            'note' => 'inbox name test',
            'bundle_key' => 'bundle-inbox',
            'role' => 'main',
            'user_id' => $user->getKey(),
        ]);
    }

}
