<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class UserInactivityReminderMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public CarbonInterface $lastLoginAt,
    ) {
        $this->subjectLine = __('mails.user_inactivity_reminder.subject');
    }

    protected function viewName(): string
    {
        return 'emails.user-inactivity-reminder';
    }

    protected function viewData(): array
    {
        return [
            'user' => $this->user,
            'lastLoginAt' => $this->lastLoginAt,
            'loginUrl' => route('filament.standard.auth.login'),
        ];
    }
}
