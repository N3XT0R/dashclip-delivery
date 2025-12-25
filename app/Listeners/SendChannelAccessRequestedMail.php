<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Channel\ChannelAccessRequested;

class SendChannelAccessRequestedMail
{
    public function handle(ChannelAccessRequested $event): void
    {
        $user = $event->applicant;
        $channel = $event->channel;
        $application = $event->channelApplication;
    }
}
