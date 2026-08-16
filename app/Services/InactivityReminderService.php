<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\UserRepository;

class InactivityReminderService
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {
    }

    /**
     * Notify all eligible users about their inactivity and record that a reminder was sent.
     * @param int $days
     * @return int Number of users processed.
     */
    public function notify(int $days = 7): int
    {
        $users = $this->userRepository->getUsersEligibleForInactivityReminder($days);

        foreach ($users as $user) {
            $this->notificationService->notifyUserInactivity($user, $user->last_login_at);
            $user->markInactivityReminderSent();
        }

        return $users->count();
    }
}
