<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:Assignment","View:Assignment","Create:Assignment","Update:Assignment","Delete:Assignment","Restore:Assignment","ForceDelete:Assignment","ForceDeleteAny:Assignment","RestoreAny:Assignment","Replicate:Assignment","Reorder:Assignment","ViewAny:Batch","View:Batch","Create:Batch","Update:Batch","Delete:Batch","Restore:Batch","ForceDelete:Batch","ForceDeleteAny:Batch","RestoreAny:Batch","Replicate:Batch","Reorder:Batch","ViewAny:Channel","View:Channel","Create:Channel","Update:Channel","Delete:Channel","Restore:Channel","ForceDelete:Channel","ForceDeleteAny:Channel","RestoreAny:Channel","Replicate:Channel","Reorder:Channel","ViewAny:Config","View:Config","Create:Config","Update:Config","Delete:Config","Restore:Config","ForceDelete:Config","ForceDeleteAny:Config","RestoreAny:Config","Replicate:Config","Reorder:Config","ViewAny:Download","View:Download","Create:Download","Update:Download","Delete:Download","Restore:Download","ForceDelete:Download","ForceDeleteAny:Download","RestoreAny:Download","Replicate:Download","Reorder:Download","ViewAny:MailLog","View:MailLog","Create:MailLog","Update:MailLog","Delete:MailLog","Restore:MailLog","ForceDelete:MailLog","ForceDeleteAny:MailLog","RestoreAny:MailLog","Replicate:MailLog","Reorder:MailLog","ViewAny:Notification","View:Notification","Create:Notification","Update:Notification","Delete:Notification","Restore:Notification","ForceDelete:Notification","ForceDeleteAny:Notification","RestoreAny:Notification","Replicate:Notification","Reorder:Notification","ViewAny:Page","View:Page","Create:Page","Update:Page","Delete:Page","Restore:Page","ForceDelete:Page","ForceDeleteAny:Page","RestoreAny:Page","Replicate:Page","Reorder:Page","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:DropboxConnect","View:VideoUpload","View:ListLogs","View:ViewLog","ViewAny:Activity","View:Activity","Create:Activity","Update:Activity","Delete:Activity","Restore:Activity","ForceDelete:Activity","ForceDeleteAny:Activity","RestoreAny:Activity","Replicate:Activity","Reorder:Activity","ViewAny:User","View:User","Create:User","Update:User","Delete:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:OfferLinkClick","View:OfferLinkClick","Create:OfferLinkClick","Update:OfferLinkClick","Delete:OfferLinkClick","Restore:OfferLinkClick","ForceDelete:OfferLinkClick","ForceDeleteAny:OfferLinkClick","RestoreAny:OfferLinkClick","Replicate:OfferLinkClick","Reorder:OfferLinkClick","ViewAny:Team","View:Team","Create:Team","Update:Team","Delete:Team","Restore:Team","ForceDelete:Team","ForceDeleteAny:Team","RestoreAny:Team","Replicate:Team","Reorder:Team","View:OnboardingWizard","ManageChannels:Team","ViewAny:ChannelTeamPivot","View:ChannelTeamPivot","Create:ChannelTeamPivot","Update:ChannelTeamPivot","Delete:ChannelTeamPivot","Restore:ChannelTeamPivot","ForceDelete:ChannelTeamPivot","ForceDeleteAny:ChannelTeamPivot","RestoreAny:ChannelTeamPivot","Replicate:ChannelTeamPivot","Reorder:ChannelTeamPivot","ViewAny:ChannelApplication","View:ChannelApplication","Create:ChannelApplication","Update:ChannelApplication","Delete:ChannelApplication","Restore:ChannelApplication","ForceDelete:ChannelApplication","ForceDeleteAny:ChannelApplication","RestoreAny:ChannelApplication","Replicate:ChannelApplication","Reorder:ChannelApplication"]},{"name":"panel_user","guard_name":"web","permissions":["ViewAny:Assignment","View:Assignment","Create:Assignment","Update:Assignment","Delete:Assignment","Restore:Assignment","ForceDelete:Assignment","ForceDeleteAny:Assignment","RestoreAny:Assignment","Replicate:Assignment","Reorder:Assignment","ViewAny:Batch","View:Batch","Create:Batch","Update:Batch","Delete:Batch","Restore:Batch","ForceDelete:Batch","ForceDeleteAny:Batch","RestoreAny:Batch","Replicate:Batch","Reorder:Batch","ViewAny:Channel","View:Channel","Create:Channel","Update:Channel","Delete:Channel","Restore:Channel","ForceDelete:Channel","ForceDeleteAny:Channel","RestoreAny:Channel","Replicate:Channel","Reorder:Channel","ViewAny:Download","View:Download","Create:Download","Update:Download","Delete:Download","Restore:Download","ForceDelete:Download","ForceDeleteAny:Download","RestoreAny:Download","Replicate:Download","Reorder:Download","ViewAny:MailLog","View:MailLog","Create:MailLog","Update:MailLog","Delete:MailLog","Restore:MailLog","ForceDelete:MailLog","ForceDeleteAny:MailLog","RestoreAny:MailLog","Replicate:MailLog","Reorder:MailLog","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","ViewAny:OfferLinkClick","View:OfferLinkClick","Create:OfferLinkClick","Update:OfferLinkClick","Delete:OfferLinkClick","Restore:OfferLinkClick","ForceDelete:OfferLinkClick","ForceDeleteAny:OfferLinkClick","RestoreAny:OfferLinkClick","Replicate:OfferLinkClick","Reorder:OfferLinkClick","View:OnboardingWizard","View:ChannelApplication","View:Dashboard","View:DownloadHistory"]},{"name":"super_admin","guard_name":"standard","permissions":["ViewAny:Activity","View:Activity","Create:Activity","Update:Activity","Delete:Activity","Restore:Activity","ForceDelete:Activity","ForceDeleteAny:Activity","RestoreAny:Activity","Replicate:Activity","Reorder:Activity","ViewAny:Assignment","View:Assignment","Create:Assignment","Update:Assignment","Delete:Assignment","Restore:Assignment","ForceDelete:Assignment","ForceDeleteAny:Assignment","RestoreAny:Assignment","Replicate:Assignment","Reorder:Assignment","ViewAny:Batch","View:Batch","Create:Batch","Update:Batch","Delete:Batch","Restore:Batch","ForceDelete:Batch","ForceDeleteAny:Batch","RestoreAny:Batch","Replicate:Batch","Reorder:Batch","ViewAny:Channel","View:Channel","Create:Channel","Update:Channel","Delete:Channel","Restore:Channel","ForceDelete:Channel","ForceDeleteAny:Channel","RestoreAny:Channel","Replicate:Channel","Reorder:Channel","ViewAny:Config","View:Config","Create:Config","Update:Config","Delete:Config","Restore:Config","ForceDelete:Config","ForceDeleteAny:Config","RestoreAny:Config","Replicate:Config","Reorder:Config","ViewAny:Download","View:Download","Create:Download","Update:Download","Delete:Download","Restore:Download","ForceDelete:Download","ForceDeleteAny:Download","RestoreAny:Download","Replicate:Download","Reorder:Download","ViewAny:MailLog","View:MailLog","Create:MailLog","Update:MailLog","Delete:MailLog","Restore:MailLog","ForceDelete:MailLog","ForceDeleteAny:MailLog","RestoreAny:MailLog","Replicate:MailLog","Reorder:MailLog","ViewAny:Notification","View:Notification","Create:Notification","Update:Notification","Delete:Notification","Restore:Notification","ForceDelete:Notification","ForceDeleteAny:Notification","RestoreAny:Notification","Replicate:Notification","Reorder:Notification","ViewAny:OfferLinkClick","View:OfferLinkClick","Create:OfferLinkClick","Update:OfferLinkClick","Delete:OfferLinkClick","Restore:OfferLinkClick","ForceDelete:OfferLinkClick","ForceDeleteAny:OfferLinkClick","RestoreAny:OfferLinkClick","Replicate:OfferLinkClick","Reorder:OfferLinkClick","ViewAny:Page","View:Page","Create:Page","Update:Page","Delete:Page","Restore:Page","ForceDelete:Page","ForceDeleteAny:Page","RestoreAny:Page","Replicate:Page","Reorder:Page","ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:User","View:User","Create:User","Update:User","Delete:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:DropboxConnect","View:VideoUpload","View:ListLogs","View:ViewLog","View:OnboardingWizard","ManageChannels:Team","ViewAny:Team","View:Team","Create:Team","Update:Team","Delete:Team","Restore:Team","ForceDelete:Team","ForceDeleteAny:Team","RestoreAny:Team","Replicate:Team","Reorder:Team","ViewAny:ChannelTeamPivot","View:ChannelTeamPivot","Create:ChannelTeamPivot","Update:ChannelTeamPivot","Delete:ChannelTeamPivot","Restore:ChannelTeamPivot","ForceDelete:ChannelTeamPivot","ForceDeleteAny:ChannelTeamPivot","RestoreAny:ChannelTeamPivot","Replicate:ChannelTeamPivot","Reorder:ChannelTeamPivot","View:ChannelApplication","View:MyOffers","View:AvailableOffersStatsWidget","View:DownloadedOffersStatsWidget","View:ExpiredOffersStatsWidget","ViewAny:ChannelApplication","Create:ChannelApplication","Update:ChannelApplication","Delete:ChannelApplication","Restore:ChannelApplication","ForceDelete:ChannelApplication","ForceDeleteAny:ChannelApplication","RestoreAny:ChannelApplication","Replicate:ChannelApplication","Reorder:ChannelApplication"]},{"name":"panel_user","guard_name":"standard","permissions":["View:User","Update:User","ViewAny:Video","View:Video","Create:Video","Update:Video","Delete:Video","Restore:Video","ForceDelete:Video","ForceDeleteAny:Video","RestoreAny:Video","Replicate:Video","Reorder:Video","View:VideoUpload","View:OnboardingWizard","ManageChannels:Team","ViewAny:ChannelTeamPivot","View:ChannelTeamPivot","Create:ChannelTeamPivot","Update:ChannelTeamPivot","Delete:ChannelTeamPivot","Restore:ChannelTeamPivot","ForceDelete:ChannelTeamPivot","ForceDeleteAny:ChannelTeamPivot","RestoreAny:ChannelTeamPivot","Replicate:ChannelTeamPivot","Reorder:ChannelTeamPivot","View:ChannelApplication"]},{"name":"channel_operator","guard_name":"standard","permissions":["View:OnboardingWizard","ManageChannels:Team","View:ChannelApplication","View:MyOffers","View:AvailableOffersStatsWidget","View:DownloadedOffersStatsWidget","View:ExpiredOffersStatsWidget"]}]';
        $directPermissions = '{"171":{"name":"View:SelectChannels","guard_name":"web"},"343":{"name":"manage_channels:_team","guard_name":"standard"},"370":{"name":"View:BaseChannelWidget","guard_name":"standard"}}';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = 'App\Models\Team';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

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

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
