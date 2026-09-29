<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Exceptions\Video\VideoFileRemovalException;
use App\Models\Clip;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class VideoServiceRemoveStoredFilesTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('previews');
    }

    public function testRemovesTheVideoFileAndThePreviewsOfDeletedClips(): void
    {
        $video = Video::factory()->create(['disk' => 'local']);
        Storage::disk('local')->put($video->path, 'video-bytes');
        $clip = Clip::factory()->forVideo($video)->create([
            'preview_disk' => 'previews',
            'preview_path' => 'previews/clip.mp4',
        ]);
        Storage::disk('previews')->put('previews/clip.mp4', 'preview-bytes');
        $video->delete(); // soft-deletes the clips as well (VideoObserver::deleting)

        app(VideoService::class)->removeStoredFiles($video);

        Storage::disk('local')->assertMissing($video->path);
        Storage::disk('previews')->assertMissing('previews/clip.mp4');
        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        $this->assertSoftDeleted('clips', ['id' => $clip->getKey()]);
    }

    public function testMissingFilesAreNotAnError(): void
    {
        $video = Video::factory()->create(['disk' => 'local']);
        Clip::factory()->forVideo($video)->create(['preview_disk' => null, 'preview_path' => null]);

        app(VideoService::class)->removeStoredFiles($video);

        Storage::disk('local')->assertMissing($video->path);
    }

    public function testAnUnusableDiskIsReportedAsVideoFileRemovalException(): void
    {
        $video = Video::factory()->create(['disk' => 'broken']);

        $this->expectException(VideoFileRemovalException::class);

        app(VideoService::class)->removeStoredFiles($video);
    }

    public function testCleanupRemovesBothVersionsAndCanBeRepeated(): void
    {
        $video = Video::factory()->create(['disk' => 'local', 'source_path' => 'original.mp4']);
        Storage::disk('local')->put($video->path, 'blurred');
        Storage::disk('local')->put('original.mp4', 'original');

        app(VideoService::class)->removeStoredFiles($video);
        app(VideoService::class)->removeStoredFiles($video);

        Storage::disk('local')->assertMissing([$video->path, 'original.mp4']);
        self::assertNotNull($video->fresh());
    }

    public function testCleanupRemovesOriginalEvenWhenTheBlurredFileIsAlreadyMissing(): void
    {
        $video = Video::factory()->create(['disk' => 'local', 'source_path' => 'original.mp4']);
        Storage::disk('local')->put('original.mp4', 'original');

        app(VideoService::class)->removeStoredFiles($video);

        Storage::disk('local')->assertMissing('original.mp4');
    }

    public function testDirectDeletionRemovesBothVersions(): void
    {
        $video = Video::factory()->create(['disk' => 'local', 'source_path' => 'original.mp4']);
        Storage::disk('local')->put($video->path, 'blurred');
        Storage::disk('local')->put('original.mp4', 'original');

        self::assertTrue(app(VideoService::class)->delete($video));

        Storage::disk('local')->assertMissing([$video->path, 'original.mp4']);
        $this->assertSoftDeleted($video);
    }

    public function testDuplicateDeletionRemovesBothVersions(): void
    {
        $video = Video::factory()->create(['disk' => 'local', 'source_path' => 'original.mp4', 'team_id' => null]);
        Storage::disk('local')->put($video->path, 'blurred');
        Storage::disk('local')->put('original.mp4', 'original');

        self::assertTrue(app(VideoService::class)->deleteDuplicateVideo($video));

        Storage::disk('local')->assertMissing([$video->path, 'original.mp4']);
        $this->assertSoftDeleted($video);
    }
}
