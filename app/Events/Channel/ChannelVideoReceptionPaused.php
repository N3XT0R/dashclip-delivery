<?php

declare(strict_types=1);

namespace App\Events\Channel;

use App\Models\Channel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelVideoReceptionPaused implements ShouldQueue, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Channel $channel,
    ) {
    }
}
