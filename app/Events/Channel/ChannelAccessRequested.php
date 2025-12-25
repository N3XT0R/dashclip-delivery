<?php

declare(strict_types=1);

namespace App\Events\Channel;

use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelAccessRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChannelApplication $channelApplication,
        public readonly User $applicant,
        public readonly Channel $channel
    ) {
    }
}
