<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Channel\ChannelAccessRequested;
use App\Services\MailService;

class SendChannelAccessRequestedMail
{
    public function handle(ChannelAccessRequested $event): void
    {
        app(MailService::class)->sendChannelAccessApprovalRequestedMail(
            $event->channelApplication->channel->email,
            $event->channelApplication,
        );
    }
}
