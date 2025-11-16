<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\User;

class UserRepository
{
    public function getUserByDisplayName(string $displayName): ?User
    {
        return User::query()
            ->where('users.submitted_name', $displayName)
            ->orWhere('users.name', $displayName)
            ->first();
    }
}