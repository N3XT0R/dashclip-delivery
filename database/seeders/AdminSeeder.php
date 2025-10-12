<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:Assignment","View:Assignment","Create:Assignment","Update:Assignment","Delete:Assignment","Restore:Assignment","ForceDelete:Assignment","ForceDeleteAny:Assignment","RestoreAny:Assignment","Replicate:Assignment","Reorder:Assignment","ViewAny:Batch","View:Batch","Create:Batch","Update:Batch","Delete:Batch","Restore:Batch","ForceDelete:Batch","ForceDeleteAny:Batch","RestoreAny:Batch","Replicate:Batch","Reorder:Batch","ViewAny:Channel","View:Channel","Create:Channel","Update:Channel","Delete:Channel","Restore:Channel","ForceDelete:Channel","ForceDeleteAny:Channel","RestoreAny:Channel","Replicate:Channel","Reorder:Channel","ViewAny:Config","View:Config","Create:Config","Update:Config","Delete:Config","Restore:Config","ForceDelete:Config","ForceDeleteAny:Config","RestoreAny:Config","Replicate:Config","Reorder:Config","ViewAny:Download","View:Download","Create:Download","Update:Download","Delete:Download","Restore:Download","ForceDelete:Download","ForceDeleteAny:Download","RestoreAny:Download","Replicate:Download","Reorder:Download","ViewAny:MailLog","View:MailLog","Create:MailLog","Update:MailLog","Delete:MailLog","Restore:MailLog","ForceDelete:MailLog","ForceDeleteAny:MailLog","RestoreAny:MailLog","Replicate:MailLog","Reorder:MailLog","ViewAny:Notification","View:Notification","Create:Notification","Update:Notification","Delete:Notification","Restore:Notification","ForceDelete:Notification","ForceDeleteAny:Notification","RestoreAny:Notification","Replicate:Notification","Reorder:Notification","ViewAny:Page","View:Page","Create:Page","Update:Page","Delete:Page","Restore:Page","ForceDelete:Page","ForceDeleteAny:Page","RestoreAny:Page","Replicate:Page","Reorder:Page","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:DropboxConnect","View:VideoUpload","View:ListLogs","View:ViewLog"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (!blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (!blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (!blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
