<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Facades\PathBuilder;
use App\Models\Video;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\DatabaseTestCase;

final class VideoObserverTest extends DatabaseTestCase
{
    public function testDeletingRemovesVideoAndPreviewFiles(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $hash = '0c8f4a3bce2b4a5a92c8845b0fb40f2c0f8fa4c5a0d4880f17c5b5e3a1e7b6d3';
        $videoPath = 'videos/sample.mp4';
        $previewPath = PathBuilder::forPreviewByHash($hash);

        Storage::disk('local')->put($videoPath, 'video-content');
        Storage::disk('local')->put($previewPath, 'preview-content');
        Storage::disk('public')->put($previewPath, 'preview-content');

        $video = Video::factory()->create([
            'hash' => $hash,
            'path' => $videoPath,
            'disk' => 'local',
        ]);

        $this->assertTrue($video->delete());

        Storage::disk('local')->assertMissing($videoPath);
        Storage::disk('public')->assertMissing($previewPath);
    }

    public function testDeletingStopsWhenPrimaryFileDeletionFails(): void
    {
        $hash = 'd2b4c9f1e3a54b6c8d9e0fa1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1';
        $videoPath = 'videos/failing.mp4';
        $previewPath = PathBuilder::forPreviewByHash($hash);

        $storageDisk = Mockery::mock(Filesystem::class);
        $storageDisk->shouldReceive('exists')->with($videoPath)->andReturn(true);
        $storageDisk->shouldReceive('delete')->with($videoPath)->andReturn(false);
        $storageDisk->shouldReceive('exists')->with($previewPath)->andReturn(false);

        $previewDisk = Mockery::mock(Filesystem::class);
        $previewDisk->shouldReceive('exists')->with($previewPath)->andReturn(false);

        Storage::shouldReceive('disk')->with('fail-disk')->andReturn($storageDisk);
        Storage::shouldReceive('disk')->with('public')->andReturn($previewDisk);

        $video = Video::factory()->create([
            'hash' => $hash,
            'path' => $videoPath,
            'disk' => 'fail-disk',
        ]);

        $this->assertFalse($video->delete());
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
