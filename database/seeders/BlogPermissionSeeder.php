<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class BlogPermissionSeeder extends Seeder
{
    private const string GUARD = 'web';

    /**
     * Register editorial permissions using the existing Shield naming convention.
     *
     * Missing permissions are created in one pass and granted to super_admin with a single call:
     * looking them up and granting them one by one flushed and reloaded the whole permission cache
     * for every permission.
     */
    public function run(): void
    {
        $names = $this->permissionNames();

        $existing = Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereIn('name', $names)
            ->pluck('name')
            ->all();

        foreach (array_diff($names, $existing) as $name) {
            Permission::query()->create(['name' => $name, 'guard_name' => self::GUARD]);
        }

        $permissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereIn('name', $names)
            ->get();

        Role::query()
            ->where('name', 'super_admin')
            ->where('guard_name', self::GUARD)
            ->first()
            ?->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function permissionNames(): array
    {
        $names = [];
        foreach (['Post', 'PostCategory', 'PostTag'] as $model) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Replicate'] as $action) {
                $names[] = $action . ':' . $model;
            }
        }

        return $names;
    }
}
