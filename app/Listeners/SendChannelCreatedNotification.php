<?php

namespace App\Listeners;

use App\Events\ChannelCreated;
use App\Services\MailService;

class SendChannelCreatedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ChannelCreated $event): void
    {
        $channel = $event->channel;

        if (!$channel->email) {
            return;
        }

        app(MailService::class)->sendChannelWelcomeMail($channel);
    }
}
