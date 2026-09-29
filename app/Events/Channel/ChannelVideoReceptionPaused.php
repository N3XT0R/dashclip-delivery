<?php

declare(strict_types=1);

namespace App\Events\Channel;

use App\Events\AbstractQueuedEvent;
use App\Models\Channel;

class ChannelVideoReceptionPaused extends AbstractQueuedEvent
{
    public function __construct(
        public readonly Channel $channel,
    ) {
    }
}
