<?php

namespace App\Listeners;

use App\Events\ChannelCreated;
use App\Mail\ChannelWelcomeMail;
use Illuminate\Support\Facades\Mail;

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

        Mail::to($channel->email)->send(new ChannelWelcomeMail($channel));
    }
}
