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
    /**
     * Get Role model by RoleEnum
     * @param  RoleEnum  $roleEnum
     * @param  string|null  $guard
     * @return Role
     */
    public function getRoleByRoleEnum(RoleEnum $roleEnum, ?string $guard = null): Role
    {
        return Role::query()
            ->where('name', $roleEnum->value)
            ->where('guard_name', $guard ?? config('auth.defaults.guard', GuardEnum::DEFAULT->value))
            ->firstOrFail();
    }

    /**
     * Check if user has all roles to access everything
     * @param  User  $user
     * @return bool
     */
    public function canAccessEverything(User $user): bool
    {
        return $user->hasAllRoles([
            RoleEnum::SUPER_ADMIN
        ]);
    }

    /**
     * Check if user can access a given Filament panel
     * @param  User  $user
     * @param  Panel  $panel
     * @return bool
     */
    public function canAccessPanel(User $user, Panel $panel): bool
    {
        if ($this->canAccessEverything($user)) {
            return true;
        }

        return $user->roles()->where('guard_name', $panel->getAuthGuard())->exists();
    }

    /**
     * Assign a role to a user
     * @param  User  $user
     * @param  RoleEnum  $roleEnum
     * @param  string|null  $guard
     * @return bool
     */
    public function giveRoleToUser(User $user, RoleEnum $roleEnum, ?string $guard = null): bool
    {
        $role = $this->getRoleByRoleEnum($roleEnum, $guard);
        $user->assignRole($role);

        return $user->hasRole($role);
    }

    /**
     * Check if user has a specific role
     * @param  User  $user
     * @param  RoleEnum  $roleEnum
     * @param  string|null  $guard
     * @return bool
     */
    public function hasRole(User $user, RoleEnum $roleEnum, ?string $guard = null): bool
    {
        $role = $this->getRoleByRoleEnum($roleEnum, $guard);
        return $user->hasRole($role);
    }
}