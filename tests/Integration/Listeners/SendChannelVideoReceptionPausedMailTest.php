<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Listeners\SendChannelVideoReceptionPausedMail;
use App\Mail\ChannelVideoReceptionPausedMail;
use App\Models\Channel;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

final class SendChannelVideoReceptionPausedMailTest extends DatabaseTestCase
{
    public function testListenerQueuesMailWhenEmailIsPresent(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create(['email' => 'owner@example.com']);
        $event = new ChannelVideoReceptionPaused($channel);

        (new SendChannelVideoReceptionPausedMail())->handle($event);

        Mail::assertQueued(
            ChannelVideoReceptionPausedMail::class,
            static fn(ChannelVideoReceptionPausedMail $mail) =>
                $mail->hasTo('owner@example.com') &&
                $mail->channel->is($channel)
        );
    }

    public function testListenerDoesNothingWhenEmailIsMissing(): void
    {
        Mail::fake();

        $channel = Channel::factory()->make(['email' => null]);
        $event = new ChannelVideoReceptionPaused($channel);

        (new SendChannelVideoReceptionPausedMail())->handle($event);

        Mail::assertNothingSent();
    }

    public function testMailIsQueuedWhenChannelReceptionIsPausedViaEloquent(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create([
            'email'                    => 'owner@example.com',
            'is_video_reception_paused' => false,
        ]);

        // Trigger via Eloquent — exercises the full observer → event → listener chain
        $channel->update(['is_video_reception_paused' => true]);

        Mail::assertQueued(
            ChannelVideoReceptionPausedMail::class,
            static fn(ChannelVideoReceptionPausedMail $mail) =>
                $mail->hasTo('owner@example.com') &&
                $mail->channel->is($channel)
        );
    }
}
