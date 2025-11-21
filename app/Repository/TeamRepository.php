<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Team;
use App\Models\User;

class TeamRepository
{
    public function createOwnTeamForUser(User $user): void
    {
        $team = Team::create([
            'name' => $user->name."'s Team",
            'owner_id' => $user->getKey(),
        ]);

        $user->teams()->attach($team);
    }
}