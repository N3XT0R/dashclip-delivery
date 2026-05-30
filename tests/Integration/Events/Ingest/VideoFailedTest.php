<?php

declare(strict_types=1);

namespace Tests\Integration\Events\Ingest;

use App\Events\Ingest\VideoFailed;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Event;
use Tests\DatabaseTestCase;

final class VideoFailedTest extends DatabaseTestCase
{
    public function testItStoresVideoAndUserOnConstruction(): void
    {
        $video = Video::factory()->create();
        $user = User::factory()->create();

        $event = new VideoFailed($video, $user);

        $this->assertTrue($event->video->is($video));
        $this->assertTrue($event->user->is($user));
    }

    public function testUserIsOptionalAndDefaultsToNull(): void
    {
        $video = Video::factory()->create();

        $event = new VideoFailed($video);

        $this->assertNull($event->user);
        $this->assertTrue($event->video->is($video));
    }

    public function testItIsDispatchableAndReceivableViaEventFake(): void
    {
        Event::fake();

        $video = Video::factory()->create();
        $user = User::factory()->create();

        VideoFailed::dispatch($video, $user);

        Event::assertDispatched(VideoFailed::class, function (VideoFailed $event) use ($video, $user) {
            return $event->video->is($video)
                && $event->user->is($user);
        });
    }

    public function testItCanBeDispatchedWithoutUser(): void
    {
        Event::fake();

        $video = Video::factory()->create();

        VideoFailed::dispatch($video);

        Event::assertDispatched(VideoFailed::class, function (VideoFailed $event) use ($video) {
            return $event->video->is($video)
                && $event->user === null;
        });
    }
}
