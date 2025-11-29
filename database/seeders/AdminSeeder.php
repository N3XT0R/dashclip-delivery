<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $adminRole = Role::where('name', 'super_admin')->firstOrFail();

        User::all()->each(function (User $user) use ($adminRole) {
            $user->syncRoles([$adminRole]);
        });

        $this->command->info('All existing users assigned to the admin role.');
    }
}
