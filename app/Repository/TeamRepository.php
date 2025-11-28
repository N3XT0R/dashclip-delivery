<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Team;
use App\Models\User;

class TeamRepository
{
    public function createOwnTeamForUser(User $user): Team
    {
        $team = Team::query()->create([
            'name' => $user->name."'s Team",
            'owner_id' => $user->getKey(),
        ]);

        $user->teams()->attach($team);

        return $team;
    }


    public function getDefaultTeamForUser(User $user): ?Team
    {
        return $user->teams()->isOwnTeam($user)->first();
    }

    public function canAccessTeam(User $user, Team $team): bool
    {
        return $user->teams()->where('teams.id', $team->getKey())->exists();
    }

    public function isUserOwnerOfTeam(User $user, Team $team): bool
    {
        return $team->owner_id === $user->getKey();
    }

    public function isMemberOfTeam(User $user, Team $team): bool
    {
        return $team->users()->has($user)->exists();
    }

}