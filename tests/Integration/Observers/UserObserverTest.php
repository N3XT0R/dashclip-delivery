<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Observers\UserObserver;
use Spatie\Permission\PermissionRegistrar;
use Tests\DatabaseTestCase;

final class UserObserverTest extends DatabaseTestCase
{
    public function testCreatedUserGetsDefaultRoleAndOwnTeam(): void
    {
        Role::query()->firstOrCreate([
            'name' => RoleEnum::REGULAR->value,
            'guard_name' => GuardEnum::STANDARD->value,
        ]);

        $user = User::factory()->create([
            'name' => 'Observer User',
            'email' => 'observer@example.com',
        ]);

        $this->assertTrue(
            $user->hasRole(RoleEnum::REGULAR->value, GuardEnum::STANDARD->value)
        );

        $ownedTeam = Team::query()->where('owner_id', $user->getKey())->first();
        $this->assertNotNull($ownedTeam);
        $this->assertTrue($user->teams->contains($ownedTeam));
    }

    public function testAddsDefaultRoleAlongsideExistingRoles(): void
    {
        $defaultRole = Role::query()->firstOrCreate([
            'name' => RoleEnum::REGULAR->value,
            'guard_name' => GuardEnum::STANDARD->value,
        ]);

        $existingRole = Role::query()->firstOrCreate([
            'name' => 'existing-role',
            'guard_name' => GuardEnum::STANDARD->value,
        ]);

        $user = User::withoutEvents(static fn() => User::factory()->create([
            'name' => 'Observer User Roles',
        ]));

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->assignRole($existingRole);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $existingRole->getKey(),
            'model_id' => $user->getKey(),
            'model_type' => User::class,
        ]);

        $observer = $this->app->make(UserObserver::class);
        $observer->created($user);

        $freshUser = $user->fresh();

        $this->assertTrue($freshUser->hasRole($defaultRole->name, GuardEnum::STANDARD->value));
        $this->assertTrue($freshUser->hasRole($existingRole->name, GuardEnum::STANDARD->value));
    }
}
