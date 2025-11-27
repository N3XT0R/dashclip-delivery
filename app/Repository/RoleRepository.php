<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Filament\Panel;


class RoleRepository
{
    public function getRoleByRoleEnum(RoleEnum $roleEnum, ?string $guard = null): Role
    {
        return Role::query()
            ->where('name', $roleEnum->value)
            ->where('guard_name', $guard ?? config('auth.defaults.guard', GuardEnum::DEFAULT->value))
            ->firstOrFail();
    }

    public function canAccessEverything(User $user): bool
    {
        return $user->hasAllRoles([
            RoleEnum::SUPER_ADMIN
        ]);
    }

    public function canAccessPanel(User $user, Panel $panel): bool
    {
        if ($this->canAccessEverything($user)) {
            return true;
        }

        return $user->roles()->where('guard_name', $panel->getAuthGuard())->exists();
    }
}