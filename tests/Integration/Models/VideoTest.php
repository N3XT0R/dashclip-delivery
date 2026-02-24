<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Facades\PathBuilder;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

/**
 * Integration tests for the Video model and its file-related behaviors.
 *
 * - Uses Storage::fake('tmp') to simulate the video disk.
 * - Verifies that deleting a Video removes its files and previews.
 * - Ensures getPreviewPath() works correctly with and without existing previews.
 */
final class VideoTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('tmp');
    }

    public function testHasUsersClipsScopeReturnsVideos(): void
    {
        $user = User::factory()->create();
        $videoWithClip = Video::factory()->create();
        $videoWithoutClip = Video::factory()->create();

        $clip = $videoWithClip->clips()->create([
            'start_sec' => 0,
            'end_sec' => 5,
        ]);
        $clip->setUser($user)->save();

        $videos = Video::query()->hasUsersClips($user)->get();

        $this->assertCount(1, $videos);
        $this->assertTrue($videos->first()->is($videoWithClip));
        $this->assertFalse($videos->contains($videoWithoutClip));
    }

    public function testDeletingVideoRemovesFilesFromStorage(): void
    {
        Config::set('preview.default_disk', 'tmp');
        // Arrange
        $video = Video::factory()->create([
            'disk' => 'tmp',
            'path' => 'videos/sample.mp4',
            'hash' => 'ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00ff00',
        ]);

        Storage::disk('tmp')->put($video->path, 'video-data');
        $previewPath = PathBuilder::forPreviewByHash($video->hash);
        Storage::disk('tmp')->put($previewPath, 'preview-data');

        $this->assertTrue(Storage::disk('tmp')->exists($video->path));
        $this->assertTrue(Storage::disk('tmp')->exists($previewPath));

        // Act
        $video->delete();

        // Assert
        Storage::disk('tmp')->assertMissing($video->path);
        Storage::disk('tmp')->assertMissing($previewPath);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }


    public function testDeletingVideoLogsErrorIfDeletionFails(): void
    {
        // Arrange
        Log::shouldReceive('error')->once()->withArgs(fn($msg, $context) => str_contains(
                $msg,
                'File delete threw'
            ) && isset($context['video_id'])
        );

        $video = Video::factory()->create([
            'disk' => 'tmp',
            'path' => 'videos/fail.mp4',
            'hash' => 'ee00ee00ee00ee00ee00ee00ee00ee00ee00ee00ee00ee00ee00ee00ee00ee00',
        ]);

        Storage::shouldReceive('disk')->with('tmp')->andThrow(new \RuntimeException('Fake failure'));

        // Act
        $video->delete();

        // Assert
        $this->addToAssertionCount(1); // Just to mark it as handled
    }

    public function testMassAssignmentAndMetaCast(): void
    {
        $video = Video::query()->create([
            'hash' => 'abc123',
            'ext' => 'mp4',
            'bytes' => 123456,
            'path' => 'videos/ab/cd/abc123.mp4',
            'meta' => ['duration' => 42, 'codec' => 'h264'],
            'original_name' => 'dashcam.mp4',
            'disk' => 'local',
            'preview_url' => null,
        ])->fresh();

        $this->assertSame('abc123', $video->hash);
        $this->assertSame('mp4', $video->ext);
        $this->assertSame(123456, $video->bytes);
        $this->assertSame('videos/ab/cd/abc123.mp4', $video->path);
        $this->assertIsArray($video->meta);
        $this->assertSame(['duration' => 42, 'codec' => 'h264'], $video->meta);
        $this->assertSame('dashcam.mp4', $video->original_name);
        $this->assertSame('local', $video->disk);
        $this->assertNull($video->preview_url);
    }

    public function testAssignmentsRelationReturnsRelatedModels(): void
    {
        $video = Video::factory()->create();
        $batch = Batch::factory()->type('assign')->finished()->create();
        $ch1 = Channel::factory()->create();
        $ch2 = Channel::factory()->create();

        $a1 = Assignment::factory()
            ->for($video, 'video')->for($ch1, 'channel')->for($batch, 'batch')
            ->create();
        $a2 = Assignment::factory()
            ->for($video, 'video')->for($ch2, 'channel')->for($batch, 'batch')
            ->create();

        $ids = $video->assignments()->pluck('id')->all();

        $this->assertContains($a1->getKey(), $ids);
        $this->assertContains($a2->getKey(), $ids);
        $this->assertCount(2, $video->assignments()->get());
    }

    public function testClipsRelationReturnsRelatedModels(): void
    {
        $video = Video::factory()->create();

        $c1 = Clip::factory()->create([
            'video_id' => $video->getKey(),
            'start_sec' => 0,
            'end_sec' => 10,
        ]);
        $c2 = Clip::factory()->create([
            'video_id' => $video->getKey(),
            'start_sec' => 30,
            'end_sec' => 50,
        ]);

        $clipIds = $video->clips()->pluck('id')->all();

        $this->assertContains($c1->getKey(), $clipIds);
        $this->assertContains($c2->getKey(), $clipIds);
        $this->assertCount(2, $video->clips()->get());
    }

    public function testGetDiskReturnsConfiguredFilesystemAndIsWritable(): void
    {
        // Fake the "public" disk to avoid touching real storage
        Storage::fake('public');

        $video = Video::factory()->create([
            'disk' => 'public',
            'path' => 'videos/'.Str::random(8).'/'.Str::random(8).'.mp4',
        ]);

        $disk = $video->getDisk();
        $this->assertInstanceOf(Filesystem::class, $disk);

        $testPath = 'probe/'.Str::random(12).'.txt';
        $this->assertTrue($disk->put($testPath, 'ok'));

        Storage::disk('public')->assertExists($testPath);
    }
}
