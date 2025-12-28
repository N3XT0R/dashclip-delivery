<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Channel\ChannelAccessRequested;
use App\Services\MailService;

class SendChannelAccessRequestedMail
{
    public function handle(ChannelAccessRequested $event): void
    {
        $channelApplication = $event->channelApplication;
        $email = $event->channelApplication->channel->email ?? '';


        app(MailService::class)->sendChannelAccessApprovalRequestedMail(
            owner: $email,
            channelApplication: $channelApplication,
        );
    }
}
