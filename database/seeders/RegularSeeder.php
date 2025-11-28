<?php

namespace Database\Seeders;

use App\Enum\Guard\GuardEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RegularSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncPermissionsForAdminPanel();
        $this->syncPermissionsForStandardPanel();

        $this->command->info('panel_user seeded with all permissions except Config.');
    }

    protected function syncPermissionsForAdminPanel(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::pluck('name')->toArray();
        $excludedResources = [
            'Config',
            'Role',
            'Page',
            'User',
            'Dropbox',
            'Activity'
        ];

        $role = Role::firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => GuardEnum::DEFAULT->value,
        ]);

        $filteredPermissions = array_filter($allPermissions, function ($perm) use ($excludedResources) {
            return array_all($excludedResources, fn($excluded) => !str_contains($perm, $excluded));
        });

        foreach ($filteredPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $role->guard_name,
            ]);
        }

        $role->syncPermissions($filteredPermissions);
        $role->save();
    }

    protected function syncPermissionsForStandardPanel(): void
    {
        $guardName = GuardEnum::STANDARD->value;
        $patterns = [
            '%VideoUpload%',
        ];

        $permissions = Permission::query()
            ->where('guard_name', GuardEnum::DEFAULT->value)
            ->where(function ($query) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $query->orWhere('name', 'like', $pattern);
                }
            })->get();

        $role = Role::firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => $guardName,
        ]);

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm->name,
                'guard_name' => $guardName,
            ]);
        }

        $role->syncPermissions($permissions->pluck('name'));

        //give admin users access to standard panel as well
        $adminUsers = User::role('super_admin', GuardEnum::DEFAULT->value)->get();
        foreach ($adminUsers as $user) {
            $user->assignRole($role);
        }
    }

}
