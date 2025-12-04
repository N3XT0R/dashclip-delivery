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

        $adminRoles = Role::where('name', 'super_admin')->get();

        /**
         * @todo change after deployment
         */
        User::all()->each(function (User $user) use ($adminRoles) {
            $user->syncRoles($adminRoles);
        });

        $this->command->info('All existing users assigned to all super_admin roles.');
    }
}
