<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Channel\ChannelAccessRequested;
use App\Listeners\SendChannelAccessRequestedMail;
use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Models\Channel;
use App\Models\ChannelApplication;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

final class SendChannelAccessRequestedMailTest extends DatabaseTestCase
{
    public function testItSendsChannelAccessRequestedMail(): void
    {
        Mail::fake();

        // Arrange
        $channel = Channel::factory()->make([
            'email' => 'owner@example.com',
        ]);

        $application = ChannelApplication::factory()->make();
        $application->setRelation('channel', $channel);

        $event = new ChannelAccessRequested($application);

        // Act
        new SendChannelAccessRequestedMail()->handle($event);

        // Assert
        Mail::assertQueued(
            ChannelAccessApprovalRequestedMail::class,
            static function (ChannelAccessApprovalRequestedMail $mail) use ($application) {
                return
                    $mail->hasTo('owner@example.com') &&
                    $mail->channelApplication === $application;
            }
        );
    }
}
