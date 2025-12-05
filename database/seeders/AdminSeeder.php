<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->clearPermissionCache();

        $rolesByGuard = $this->getSuperAdminRolesGroupedByGuard();

        if ($rolesByGuard->isEmpty()) {
            $this->command->warn('AdminSeeder skipped: No super_admin roles found.');
            return;
        }

        $rolesByGuard->each(function (Collection $roles, string $guard) {
            $this->syncRolesForGuardIfNeeded($guard, $roles);
        });
    }

    /**
     * Ensures Spatie Permission does not use stale cache.
     */
    protected function clearPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Returns all super_admin roles grouped by guard_name.
     */
    protected function getSuperAdminRolesGroupedByGuard(): Collection
    {
        return Role::query()
            ->where('name', 'super_admin')
            ->get()
            ->groupBy('guard_name');
    }

    /**
     * Syncs super_admin roles to all users only if the guard
     * does not yet have anyone with this role.
     */
    protected function syncRolesForGuardIfNeeded(string $guard, Collection $roles): void
    {
        if ($this->guardAlreadyHasSuperAdmin($guard)) {
            $this->command->info("Guard '{$guard}' already has super_admin users. Skipping.");
            return;
        }

        $this->assignRolesToAllUsers($roles);

        $this->command->info("Assigned super_admin roles for guard '{$guard}' to all users.");
    }

    /**
     * Checks if any user already has super_admin for the given guard.
     */
    protected function guardAlreadyHasSuperAdmin(string $guard): bool
    {
        return User::role('super_admin', $guard)->exists();
    }

    /**
     * Assigns the given roles to every user.
     */
    protected function assignRolesToAllUsers(Collection $roles): void
    {
        User::all()->each(function (User $user) use ($roles) {
            $roles->each(fn(Role $role) => $user->assignRole($role));
        });
    }
}
