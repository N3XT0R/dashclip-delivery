<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Video\VideoQueuedForIngest;
use App\Jobs\ProcessVideoIngestJob;
use App\Listeners\DispatchVideoIngestJobListener;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\DatabaseTestCase;

final class DispatchVideoIngestJobListenerTest extends DatabaseTestCase
{
    private DispatchVideoIngestJobListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = $this->app->make(DispatchVideoIngestJobListener::class);
    }

    public function testItDispatchesProcessVideoIngestJobWithCorrectVideoId(): void
    {
        Bus::fake();

        $video = Video::factory()->create();
        $event = new VideoQueuedForIngest($video);

        $this->listener->handle($event);

        Bus::assertDispatched(ProcessVideoIngestJob::class, function ($job) use ($video) {
            return $job->videoId === $video->getKey();
        });
    }

    public function testJobIsDispatchedWithDelay(): void
    {
        Bus::fake();

        $video = Video::factory()->create();
        $event = new VideoQueuedForIngest($video);

        $this->listener->handle($event);

        Bus::assertDispatched(ProcessVideoIngestJob::class, function ($job) {
            return $job->delay instanceof Carbon;
        });
    }

    public function testDelayIsApproximatelyFiveSeconds(): void
    {
        Bus::fake();

        $video = Video::factory()->create();
        $event = new VideoQueuedForIngest($video);

        $before = now()->addSeconds(4);
        $after = now()->addSeconds(6);

        $this->listener->handle($event);

        Bus::assertDispatched(ProcessVideoIngestJob::class, function ($job) use ($before, $after) {
            /** @var Carbon $delay */
            $delay = $job->delay;

            return $delay instanceof Carbon
                && $delay->greaterThanOrEqualTo($before)
                && $delay->lessThanOrEqualTo($after);
        });
    }

    public function testEventDispatchTriggersListener(): void
    {
        Bus::fake();
        Event::fakeExcept([VideoQueuedForIngest::class]);

        $video = Video::factory()->create();

        event(new VideoQueuedForIngest($video));

        Bus::assertDispatched(ProcessVideoIngestJob::class, function ($job) use ($video) {
            return $job->videoId === $video->getKey();
        });
    }
}
