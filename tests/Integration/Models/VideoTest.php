<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Facades\PathBuilder;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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


    public function testGetPreviewPathReturnsPreviewByHashIfExists(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'disk' => 'tmp',
            'hash' => 'abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234',
            'ext' => 'mp4',
        ]);

        $previewPath = PathBuilder::forPreviewByHash($video->hash);
        Storage::disk('tmp')->put($previewPath, 'preview-data');

        // Act
        $result = $video->getPreviewPath();

        // Assert
        $this->assertSame($previewPath, $result);
    }


    public function testGetPreviewPathFallsBackToClipPreviewWhenMissing(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'disk' => 'tmp',
            'hash' => 'abcabcabcabcabcabcabcabcabcabcabcabcabcabcabcabcabcabcabcabcabcd'
        ]);
        $clip = Clip::factory()->create([
            'video_id' => $video->id,
            'start_sec' => 0,
            'end_sec' => 5,
        ]);

        $fallbackPath = $clip->getPreviewPath();
        Storage::disk('tmp')->put($fallbackPath, 'clip-preview');

        // Act
        $result = $video->getPreviewPath();

        // Assert
        $this->assertSame($fallbackPath, $result);
    }

    public function testGetPreviewPathReturnsNullWhenNothingExists(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'disk' => 'tmp',
            'hash' => 'hashnotfound1234567890abcdef1234567890abcdef1234567890abcdef12345678'
        ]);

        // Act
        $result = $video->getPreviewPath();

        // Assert
        $this->assertNull($result);
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
        Log::shouldReceive('error')->once()->withArgs(fn($msg, $context) => str_contains($msg,
                'File delete threw') && isset($context['video_id'])
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
}
