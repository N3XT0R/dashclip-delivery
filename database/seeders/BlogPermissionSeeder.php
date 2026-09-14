<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BlogPermissionSeeder extends Seeder
{
    /** Register editorial permissions using the existing Shield naming convention. */
    public function run(): void
    {
        foreach (['Post', 'PostCategory', 'PostTag'] as $model) {
            foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Replicate'] as $action) {
                $permission = Permission::findOrCreate($action.':'.$model, 'web');
                Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->first()?->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
