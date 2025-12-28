<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\User\UserCreated;
use App\Services\MailService;

class SendWelcomeMail
{
    public function handle(UserCreated $event): void
    {
        app(MailService::class)->sendUserWelcomeEmail(
            user: $event->user,
            fromBackend: $event->fromBackend,
            plainPassword: $event->plainPassword
        );
    }
}
