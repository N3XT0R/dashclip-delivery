<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Notifications\AbstractUserNotification;

class NotificationDiscoveryService
{
    public function list(): array
    {
        $directory = app_path('Notifications');
        $classes = [];

        foreach (scandir($directory) as $file) {
            if (!str_ends_with($file, 'Notification.php')) {
                continue;
            }

            $class = "App\\Notifications\\".pathinfo($file, PATHINFO_FILENAME);

            if (is_subclass_of($class, AbstractUserNotification::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
