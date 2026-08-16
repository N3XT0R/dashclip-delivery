<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /**
     * Get a user by their display name.
     * @param string $displayName
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

    public function getUserByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    /**
     * Users eligible for the inactivity reminder: have logged in at least once, their last login is
     * at least $days ago, no reminder was sent in the last $days (or none yet), they hold a role
     * on the standard-panel guard, and excludes anyone holding the SUPER_ADMIN role, on any guard.
     *
     * @return Collection<User>
     */
    public function getUsersEligibleForInactivityReminder(int $days = 7): Collection
    {
        $threshold = now()->subDays($days);

        return User::query()
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '<=', $threshold)
            ->where(function (Builder $query) use ($threshold) {
                $query->whereNull('last_login_reminder_sent_at')
                    ->orWhere('last_login_reminder_sent_at', '<=', $threshold);
            })
            ->whereHas('roles', fn (Builder $query) => $query->where('guard_name', GuardEnum::STANDARD->value))
            ->whereDoesntHave('roles', fn (Builder $query) => $query->where('name', RoleEnum::SUPER_ADMIN->value))
            ->get();
    }

}
