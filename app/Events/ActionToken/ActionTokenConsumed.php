<?php

declare(strict_types=1);

namespace App\Events\ActionToken;

use App\Models\ActionToken;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionTokenConsumed implements ShouldQueue, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ActionToken $token
    ) {
    }
}
