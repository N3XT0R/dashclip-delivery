<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Services\MailService;

class SendChannelVideoReceptionPausedMail
{
    public function handle(ChannelVideoReceptionPaused $event): void
    {
        $channel = $event->channel;

        if (!$channel->email) {
            return;
        }

        app(MailService::class)->sendChannelVideoReceptionPausedMail($channel);
    }
}
