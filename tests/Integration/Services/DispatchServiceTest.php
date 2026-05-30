<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Events\Video\VideoQueuedForIngest;
use App\Models\Video;
use App\Services\DispatchService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class DispatchServiceTest extends DatabaseTestCase
{
    private DispatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(DispatchService::class);
    }

    public function testDoesNotDispatchWhenVideoExistsInDatabase(): void
    {
        Event::fake();

        $video = Video::factory()->create();

        $this->service->dispatchVideoQueuedForIngest($video);

        Event::assertNotDispatched(VideoQueuedForIngest::class);
    }

    public function testDoesNotDispatchWhenVideoHasNoPath(): void
    {
        Event::fake();
        Storage::fake('local');

        $video = Video::factory()->make(['path' => null, 'disk' => 'local']);

        $this->service->dispatchVideoQueuedForIngest($video);

        Event::assertNotDispatched(VideoQueuedForIngest::class);
    }

    public function testDoesNotDispatchWhenVideoHasNoDisk(): void
    {
        Event::fake();

        $video = Video::factory()->make(['path' => 'videos/test.mp4', 'disk' => null]);

        $this->service->dispatchVideoQueuedForIngest($video);

        Event::assertNotDispatched(VideoQueuedForIngest::class);
    }

    public function testDispatchesEventWhenFileExistsOnDisk(): void
    {
        Event::fake();
        Storage::fake('local');
        Storage::disk('local')->put('videos/test.mp4', 'content');

        $video = Video::factory()->make([
            'path' => 'videos/test.mp4',
            'disk' => 'local',
        ]);

        $this->service->dispatchVideoQueuedForIngest($video);

        Event::assertDispatched(VideoQueuedForIngest::class);
    }

    public function testDispatchedEventCarriesCorrectVideo(): void
    {
        Event::fake();
        Storage::fake('local');
        Storage::disk('local')->put('videos/clip.mp4', 'content');

        $video = Video::factory()->make([
            'path' => 'videos/clip.mp4',
            'disk' => 'local',
        ]);

        $this->service->dispatchVideoQueuedForIngest($video);

        Event::assertDispatched(VideoQueuedForIngest::class, function ($event) use ($video) {
            return $event->video->getAttribute('path') === $video->getAttribute('path');
        });
    }
}
