<?php

namespace App\Repository;

use App\Models\User;
use App\Models\UserMailConfig;

class UserMailConfigRepository
{
    public function getForUser(User $user, string $key, bool $default = true): bool
    {
        $value = UserMailConfig::query()
            ->where('user_id', $user->getKey())
            ->where('key', $key)
            ->value('value');

        return $value !== null ? (bool)$value : $default;
    }

    public function setForUser(User $user, string $key, bool $value): void
    {
        UserMailConfig::updateOrCreate(
            ['user_id' => $user->getKey(), 'key' => $key],
            ['value' => $value]
        );
    }

    public function allForUser(User $user): array
    {
        return UserMailConfig::query()
            ->where('user_id', $user->getKey())
            ->pluck('value', 'key')
            ->toArray();
    }

    public function isAllowed(User $user, string $notificationClass, bool $default = true): bool
    {
        $value = UserMailConfig::where('user_id', $user->getKey())
            ->where('key', $notificationClass)
            ->value('value');

        return $value === null ? $default : (bool)$value;
    }
}
