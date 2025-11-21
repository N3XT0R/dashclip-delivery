<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;


class RoleRepository
{
    public function assignTeamRole(User $user, Role $role, Team $team): void
    {
        $user->teamRoles()->attach($role->getKey(), [
            'team_id' => $team->getKey(),
        ]);
    }
}