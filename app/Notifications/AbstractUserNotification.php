<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Repository\UserMailConfigRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class AbstractUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected bool $isConfigurable = true;

    /**
     * Whether the mail preference for this notification is offered to the given user.
     * @param User $user
     * @return bool
     */
    public static function isVisibleFor(User $user): bool
    {
        return true;
    }

    protected function notificationKey(): string
    {
        return static::class;
    }


    protected function channels(): array
    {
        return ['mail', 'database'];
    }

    public function via(mixed $notifiable): array
    {
        $channels = $this->channels();

        if (!$notifiable instanceof User) {
            return $channels;
        }

        if ($this->isConfigurable) {
            $mailAllowed = app(UserMailConfigRepository::class)
                ->isAllowed($notifiable, $this->notificationKey());

            if (!$mailAllowed) {
                $channels = array_filter($channels, fn($channel) => $channel !== 'mail');
            }
        }


        return $channels;
    }
}
