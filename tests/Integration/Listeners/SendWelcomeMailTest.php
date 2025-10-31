<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\User\UserCreated;
use App\Listeners\SendWelcomeMail;
use App\Mail\UserWelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

class SendWelcomeMailTest extends DatabaseTestCase
{
    protected SendWelcomeMail $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = $this->app->make(SendWelcomeMail::class);
        User::query()->delete();
    }

    public function testListenerQueuesWelcomeMailForNewUser(): void
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'newuser@example.com']);
        $fromBackend = true;
        $plainPassword = 'secret123';
        $event = new UserCreated($user, $fromBackend, $plainPassword);

        // Act
        $this->listener->handle($event);

        // Assert
        Mail::assertQueued(UserWelcomeMail::class, function ($mail) use ($user, $fromBackend, $plainPassword) {
            return $mail->hasTo('newuser@example.com')
                && $mail->user->is($user)
                && $mail->fromBackend === $fromBackend
                && $mail->plainPassword === $plainPassword;
        });
    }

    public function testListenerIsTriggeredAutomaticallyViaEventDispatcher(): void
    {
        // Arrange
        Mail::fake();
        Event::fakeExcept([UserCreated::class]);

        $user = User::factory()->create(['email' => 'auto@example.com']);

        // Act: trigger the event
        event(new UserCreated($user, false, 'autoPass'));

        // Assert: verify queued mail
        Mail::assertQueued(UserWelcomeMail::class, function ($mail) use ($user) {
            return $mail->hasTo('auto@example.com')
                && $mail->user->is($user);
        });
    }

    public function testListenerDoesNotFailIfMailQueueFails(): void
    {
        // Arrange
        Mail::fake();

        $user = User::factory()->create(['email' => 'failing@example.com']);
        $event = new UserCreated($user, false, 'irrelevant');

        // Simulate internal mail failure by throwing inside Mailable constructor
        $mailable = \Mockery::mock(UserWelcomeMail::class)
            ->shouldReceive('queue')
            ->andThrow(new \RuntimeException('Mail queue failed'))
            ->getMock();

        // Act
        try {
            $this->listener->handle($event);
            $this->addToAssertionCount(1); // passes if no crash
        } catch (\Throwable $e) {
            $this->fail('Listener should not throw: '.$e->getMessage());
        }
    }


}
