<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:Activity","View:Activity","Create:Activity","Update:Activity","Delete:Activity","Restore:Activity","ForceDelete:Activity","ForceDeleteAny:Activity","RestoreAny:Activity","Replicate:Activity","Reorder:Activity","ViewAny:Assignment","View:Assignment","Create:Assignment","Update:Assignment","Delete:Assignment","Restore:Assignment","ForceDelete:Assignment","ForceDeleteAny:Assignment","RestoreAny:Assignment","Replicate:Assignment","Reorder:Assignment","ViewAny:Batch","View:Batch","Create:Batch","Update:Batch","Delete:Batch","Restore:Batch","ForceDelete:Batch","ForceDeleteAny:Batch","RestoreAny:Batch","Replicate:Batch","Reorder:Batch","ViewAny:Channel","View:Channel","Create:Channel","Update:Channel","Delete:Channel","Restore:Channel","ForceDelete:Channel","ForceDeleteAny:Channel","RestoreAny:Channel","Replicate:Channel","Reorder:Channel","ViewAny:Config","View:Config","Create:Config","Update:Config","Delete:Config","Restore:Config","ForceDelete:Config","ForceDeleteAny:Config","RestoreAny:Config","Replicate:Config","Reorder:Config","ViewAny:Download","View:Download","Create:Download","Update:Download","Delete:Download","Restore:Download","ForceDelete:Download","ForceDeleteAny:Download","RestoreAny:Download","Replicate:Download","Reorder:Download","ViewAny:MailLog","View:MailLog","Create:MailLog","Update:MailLog","Delete:MailLog","Restore:MailLog","ForceDelete:MailLog","ForceDeleteAny:MailLog","RestoreAny:MailLog","Replicate:MailLog","Reorder:MailLog","ViewAny:Notification","View:Notification","Create:Notification","Update:Notification","Delete:Notification","Restore:Notification","ForceDelete:Notification","ForceDeleteAny:Notification","RestoreAny:Notification","Replicate:Notification","Reorder:Notification","ViewAny:OfferLinkClick","View:OfferLinkClick","Create:OfferLinkClick","Update:OfferLinkClick","Delete:OfferLinkClick","Restore:OfferLinkClick","ForceDelete:OfferLinkClick","ForceDeleteAny:OfferLinkClick","RestoreAny:OfferLinkClick","Replicate:OfferLinkClick","Reorder:OfferLinkClick","ViewAny:Page","View:Page","Create:Page","Update:Page","Delete:Page","Restore:Page","ForceDelete:Page","ForceDeleteAny:Page","RestoreAny:Page","Replicate:Page","Reorder:Page","ViewAny:User","View:User","Create:User","Update:User","Delete:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:DropboxConnect","View:VideoUpload","View:ListLogs","View:ViewLog","ViewAny:Team","View:Team","Create:Team","Update:Team","Delete:Team","Restore:Team","ForceDelete:Team","ForceDeleteAny:Team","RestoreAny:Team","Replicate:Team","Reorder:Team","View:OnboardingWizard"]},{"name":"panel_user","guard_name":"web","permissions":["ViewAny:Assignment","View:Assignment","Create:Assignment","Update:Assignment","Delete:Assignment","Restore:Assignment","ForceDelete:Assignment","ForceDeleteAny:Assignment","RestoreAny:Assignment","Replicate:Assignment","Reorder:Assignment","ViewAny:Batch","View:Batch","Create:Batch","Update:Batch","Delete:Batch","Restore:Batch","ForceDelete:Batch","ForceDeleteAny:Batch","RestoreAny:Batch","Replicate:Batch","Reorder:Batch","ViewAny:Channel","View:Channel","Create:Channel","Update:Channel","Delete:Channel","Restore:Channel","ForceDelete:Channel","ForceDeleteAny:Channel","RestoreAny:Channel","Replicate:Channel","Reorder:Channel","ViewAny:Download","View:Download","Create:Download","Update:Download","Delete:Download","Restore:Download","ForceDelete:Download","ForceDeleteAny:Download","RestoreAny:Download","Replicate:Download","Reorder:Download","ViewAny:MailLog","View:MailLog","Create:MailLog","Update:MailLog","Delete:MailLog","Restore:MailLog","ForceDelete:MailLog","ForceDeleteAny:MailLog","RestoreAny:MailLog","Replicate:MailLog","Reorder:MailLog","ViewAny:Notification","View:Notification","Create:Notification","Update:Notification","Delete:Notification","Restore:Notification","ForceDelete:Notification","ForceDeleteAny:Notification","RestoreAny:Notification","Replicate:Notification","Reorder:Notification","ViewAny:OfferLinkClick","View:OfferLinkClick","Create:OfferLinkClick","Update:OfferLinkClick","Delete:OfferLinkClick","Restore:OfferLinkClick","ForceDelete:OfferLinkClick","ForceDeleteAny:OfferLinkClick","RestoreAny:OfferLinkClick","Replicate:OfferLinkClick","Reorder:OfferLinkClick","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:VideoUpload","View:SelectChannels","View:OnboardingWizard"]},{"name":"super_admin","guard_name":"standard","permissions":["ViewAny:Activity","View:Activity","Create:Activity","Update:Activity","Delete:Activity","Restore:Activity","ForceDelete:Activity","ForceDeleteAny:Activity","RestoreAny:Activity","Replicate:Activity","Reorder:Activity","ViewAny:Assignment","View:Assignment","Create:Assignment","Update:Assignment","Delete:Assignment","Restore:Assignment","ForceDelete:Assignment","ForceDeleteAny:Assignment","RestoreAny:Assignment","Replicate:Assignment","Reorder:Assignment","ViewAny:Batch","View:Batch","Create:Batch","Update:Batch","Delete:Batch","Restore:Batch","ForceDelete:Batch","ForceDeleteAny:Batch","RestoreAny:Batch","Replicate:Batch","Reorder:Batch","ViewAny:Channel","View:Channel","Create:Channel","Update:Channel","Delete:Channel","Restore:Channel","ForceDelete:Channel","ForceDeleteAny:Channel","RestoreAny:Channel","Replicate:Channel","Reorder:Channel","ViewAny:Config","View:Config","Create:Config","Update:Config","Delete:Config","Restore:Config","ForceDelete:Config","ForceDeleteAny:Config","RestoreAny:Config","Replicate:Config","Reorder:Config","ViewAny:Download","View:Download","Create:Download","Update:Download","Delete:Download","Restore:Download","ForceDelete:Download","ForceDeleteAny:Download","RestoreAny:Download","Replicate:Download","Reorder:Download","ViewAny:MailLog","View:MailLog","Create:MailLog","Update:MailLog","Delete:MailLog","Restore:MailLog","ForceDelete:MailLog","ForceDeleteAny:MailLog","RestoreAny:MailLog","Replicate:MailLog","Reorder:MailLog","ViewAny:Notification","View:Notification","Create:Notification","Update:Notification","Delete:Notification","Restore:Notification","ForceDelete:Notification","ForceDeleteAny:Notification","RestoreAny:Notification","Replicate:Notification","Reorder:Notification","ViewAny:OfferLinkClick","View:OfferLinkClick","Create:OfferLinkClick","Update:OfferLinkClick","Delete:OfferLinkClick","Restore:OfferLinkClick","ForceDelete:OfferLinkClick","ForceDeleteAny:OfferLinkClick","RestoreAny:OfferLinkClick","Replicate:OfferLinkClick","Reorder:OfferLinkClick","ViewAny:Page","View:Page","Create:Page","Update:Page","Delete:Page","Restore:Page","ForceDelete:Page","ForceDeleteAny:Page","RestoreAny:Page","Replicate:Page","Reorder:Page","ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:User","View:User","Create:User","Update:User","Delete:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:DropboxConnect","View:VideoUpload","View:ListLogs","View:ViewLog","View:SelectChannels","View:OnboardingWizard","ManageChannels:Team"]},{"name":"panel_user","guard_name":"standard","permissions":["View:User","Update:User","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:VideoUpload","View:SelectChannels","View:OnboardingWizard","ManageChannels:Team"]}]';
        $directPermissions = '{"297":{"name":"manage_channels:_team","guard_name":"standard"},"298":{"name":"manage_channels:_team","guard_name":"web"}}';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
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
        if (! blank($permissions = json_decode($directPermissions, true))) {
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
