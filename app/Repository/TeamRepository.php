<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Team;
use App\Models\User;

class TeamRepository
{
    /**
     * Create a personal team for the given user.
     * @param  User  $user
     * @return Team
     */
    public function createOwnTeamForUser(User $user): Team
    {
        $team = Team::query()->create([
            'name' => $user->name."'s Team",
            'owner_id' => $user->getKey(),
        ]);

        $user->teams()->attach($team);

        return $team;
    }

    /**
     * Get the default personal team for the given user.
     * @param  User  $user
     * @return Team|null
     */
    public function getDefaultTeamForUser(User $user): ?Team
    {
        return $user->teams()->isOwnTeam($user)->first();
    }

    /**
     * Check if the user can access the given team.
     * @param  User  $user
     * @param  Team  $team
     * @return bool
     */
    public function canAccessTeam(User $user, Team $team): bool
    {
        return $user->teams()->where('teams.id', $team->getKey())->exists();
    }

    /**
     * Check if the user is the owner of the given team.
     * @param  User  $user
     * @param  Team  $team
     * @return bool
     */
    public function isUserOwnerOfTeam(User $user, Team $team): bool
    {
        return $team->owner_id === $user->getKey();
    }

    /**
     * Check if the user is a member of the given team.
     * @param  User  $user
     * @param  Team  $team
     * @return bool
     */
    public function isMemberOfTeam(User $user, Team $team): bool
    {
        return $team->users()->has($user)->exists();
    }

    /**
     * Find a team by its unique slug.
     * @param  string  $slug
     * @return Team|null
     */
    public function getTeamByUniqueSlug(string $slug): ?Team
    {
        return Team::query()
            ->where('slug', $slug)
            ->first();
    }
}