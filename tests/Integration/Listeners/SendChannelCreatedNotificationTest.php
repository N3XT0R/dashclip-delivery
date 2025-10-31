<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\ChannelCreated;
use App\Listeners\SendChannelCreatedNotification;
use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

class SendChannelCreatedNotificationTest extends DatabaseTestCase
{
    protected SendChannelCreatedNotification $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = $this->app->make(SendChannelCreatedNotification::class);
        Channel::query()->delete();
    }

    public function testListenerSendsWelcomeMailWhenEmailIsPresent(): void
    {
        // Arrange
        Mail::fake();

        $channel = Channel::factory()->create([
            'email' => 'test@example.com',
        ]);

        $event = new ChannelCreated($channel);

        // Act
        $this->listener->handle($event);

        // Assert
        Mail::assertQueued(ChannelWelcomeMail::class, function ($mail) use ($channel) {
            return $mail->hasTo('test@example.com') &&
                $mail->channel->is($channel);
        });
    }

    public function testListenerDoesNotSendMailWhenEmailIsMissing(): void
    {
        // Arrange
        Mail::fake();

        // use make() instead of create() to avoid NOT NULL violation
        $channel = Channel::factory()->make(['email' => null]);
        $event = new ChannelCreated($channel);

        // Act
        $this->listener->handle($event);

        // Assert
        Mail::assertNothingSent();
    }


    public function testEventListenerIsRegisteredAndTriggeredAutomatically(): void
    {
        // Arrange
        Mail::fake();
        Event::fakeExcept([ChannelCreated::class]);

        $channel = Channel::factory()->create(['email' => 'auto@example.com']);

        // Act: Fire event manually (simulating Laravel dispatch)
        event(new ChannelCreated($channel));

        // Assert: ensure our listener is actually called via event dispatcher
        Mail::assertQueued(ChannelWelcomeMail::class, static function ($mail) use ($channel) {
            return $mail->hasTo('auto@example.com') &&
                $mail->channel->is($channel);
        });
    }
}
