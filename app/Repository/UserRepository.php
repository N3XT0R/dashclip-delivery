<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Get a user by their display name.
     * @param  string  $displayName
     * @return User|null
     */
    public function getUserByDisplayName(string $displayName): ?User
    {
        return User::query()
            ->where('users.submitted_name', $displayName)
            ->orWhere('users.name', $displayName)
            ->first();
    }

    /**
     * Get all users.
     * @return Collection<User>
     */
    public function getAllUsers(): Collection
    {
        return User::all();
    }

    /**
     * Get all users who are not assigned to any team.
     * @return Collection<User>
     */
    public function getAllUsersWithoutTeam(): Collection
    {
        return User::doesntHave('teams')->get();
    }

    /**
     * Get the currently authenticated user.
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        return auth()->user();
    }

}