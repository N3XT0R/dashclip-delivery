<?php

declare(strict_types=1);

namespace App\Events\ActionToken;

use App\Models\ActionToken;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionTokenConsumed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ActionToken $token
    ) {
    }
}
