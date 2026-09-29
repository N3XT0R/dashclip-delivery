<?php

declare(strict_types=1);

namespace App\Events\ActionToken;

use App\Events\AbstractQueuedEvent;
use App\Models\ActionToken;

class ActionTokenConsumed extends AbstractQueuedEvent
{
    public function __construct(
        public readonly ActionToken $token
    ) {
    }
}
