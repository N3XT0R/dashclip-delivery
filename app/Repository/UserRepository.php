<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function getUserByDisplayName(string $displayName): ?User
    {
        return User::query()
            ->where('users.submitted_name', $displayName)
            ->orWhere('users.name', $displayName)
            ->first();
    }

    public function getAllUsers(): Collection
    {
        return User::all();
    }

    public function getAllUsersWithoutTeam(): Collection
    {
        return User::doesntHave('teams')->get();
    }

    public function getOwnTeam(User $user): Team
    {
        return $user->teams()->isOwnTeam($user)->firstOrFail();
    }
}