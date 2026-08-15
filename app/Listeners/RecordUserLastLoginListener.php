<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordUserLastLoginListener
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user instanceof User) {
            return;
        }

        $user->recordLogin();
    }
}
