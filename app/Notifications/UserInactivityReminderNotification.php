<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\UserInactivityReminderMail;
use App\Models\User;
use App\Notifications\Contracts\HasToMailContract;
use Carbon\CarbonInterface;

class UserInactivityReminderNotification extends AbstractUserNotification implements HasToMailContract
{
    public function __construct(public readonly CarbonInterface $lastLoginAt)
    {
    }

    protected function channels(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): UserInactivityReminderMail
    {
        return new UserInactivityReminderMail($notifiable, $this->lastLoginAt);
    }
}
