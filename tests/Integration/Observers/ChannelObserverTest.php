<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Models\Channel;
use App\Observers\ChannelObserver;
use Illuminate\Support\Facades\Event;
use Tests\DatabaseTestCase;

final class ChannelObserverTest extends DatabaseTestCase
{
    public function testFiresEventWhenReceptionIsPaused(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => false], true);
        $channel->is_video_reception_paused = true;
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertDispatched(
            ChannelVideoReceptionPaused::class,
            static fn(ChannelVideoReceptionPaused $e) => $e->channel === $channel
        );
    }

    public function testDoesNotFireEventWhenReceptionIsUnpaused(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => true], true);
        $channel->is_video_reception_paused = false;
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertNotDispatched(ChannelVideoReceptionPaused::class);
    }

    public function testDoesNotFireEventWhenPauseFieldUnchanged(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => false], true);
        $channel->name = 'changed';
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertNotDispatched(ChannelVideoReceptionPaused::class);
    }

    public function testDoesNotFireEventWhenAlreadyPausedAndPausedAgain(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => true], true);
        $channel->is_video_reception_paused = true;
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertNotDispatched(ChannelVideoReceptionPaused::class);
    }
}
