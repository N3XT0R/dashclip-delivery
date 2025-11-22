<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\Users\RoleEnum;
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

    public function getRoleByRoleEnum(RoleEnum $roleEnum, ?string $guard = null): Role
    {
        return Role::query()
            ->where('name', $roleEnum->value)
            ->where('guard_name', $guard ?? 'web')
            ->firstOrFail();
    }
}