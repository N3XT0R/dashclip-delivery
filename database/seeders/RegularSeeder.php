<?php

namespace Database\Seeders;

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

        $filteredPermissions = array_filter($allPermissions, function ($perm) use ($excludedResources) {
            return array_all($excludedResources, fn($excluded) => !str_contains($perm, $excluded));
        });
        $role = Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);
        $role->syncPermissions($filteredPermissions);
    }

    protected function syncPermissionsForStandardPanel(): void
    {
        $role = Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'standard']);
        $role->syncPermissions();
    }

}
