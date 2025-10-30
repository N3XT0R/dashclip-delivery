<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\User\UserCreated;
use App\Mail\UserWelcomeMail;
use Illuminate\Support\Facades\Mail;

class SendWelcomeMail
{
    public function handle(UserCreated $event): void
    {
        Mail::to($event->user->email)->queue(
            new UserWelcomeMail($event->user)
        );
    }
}
