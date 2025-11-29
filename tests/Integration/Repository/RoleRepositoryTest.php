<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Role;
use App\Models\User;
use App\Repository\RoleRepository;
use Filament\Panel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\DatabaseTestCase;

class RoleRepositoryTest extends DatabaseTestCase
{
    protected RoleRepository $roleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        User::flushEventListeners();
        $this->roleRepository = $this->app->make(RoleRepository::class);
        Role::query()->delete();
    }

    public function testGetsRoleByRoleEnum(): void
    {
        $role = Role::factory()->create([
            'name' => RoleEnum::SUPER_ADMIN->value,
            'guard_name' => GuardEnum::DEFAULT->value,
        ]);

        $result = $this->roleRepository->getRoleByRoleEnum(RoleEnum::SUPER_ADMIN);

        $this->assertSame($role->id, $result->id);
    }

    public function testThrowsExceptionWhenRoleDoesNotExist(): void
    {
        Role::query()->delete();
        $this->expectException(ModelNotFoundException::class);

        $this->roleRepository->getRoleByRoleEnum(RoleEnum::SUPER_ADMIN);
    }

    public function testDetectsIfUserCanAccessEverything(): void
    {
        $role = Role::factory()
            ->forRole(RoleEnum::SUPER_ADMIN)
            ->forGuard(GuardEnum::STANDARD)
            ->create();

        /** @var User $user */
        $user = User::factory()->create();
        $user->roles()->attach($role);
        $user->refresh();

        $this->assertTrue($this->roleRepository->canAccessEverything($user));
    }

    public function testDetectsIfUserCannotAccessEverything(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->assertFalse($this->roleRepository->canAccessEverything($user));
    }

    public function testAllowsPanelAccessForSuperAdmin(): void
    {
        $role = Role::factory()
            ->forRole(RoleEnum::SUPER_ADMIN)
            ->forGuard(GuardEnum::STANDARD)
            ->create();

        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('getAuthGuard')
            ->andReturn(GuardEnum::DEFAULT->value);

        /** @var User $user */
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($this->roleRepository->canAccessPanel($user, $panel));
    }

    public function testChecksPanelAccessByMatchingGuard(): void
    {
        $role = Role::factory()
            ->forRole(RoleEnum::REGULAR)
            ->forGuard(GuardEnum::STANDARD)
            ->create();

        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('getAuthGuard')
            ->andReturn(GuardEnum::STANDARD->value);

        /** @var User $user */
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($this->roleRepository->canAccessPanel($user, $panel));
    }

    public function testDeniesPanelAccessIfGuardDoesNotMatch(): void
    {
        $role = Role::factory()
            ->forRole(RoleEnum::REGULAR)
            ->forGuard(GuardEnum::STANDARD)
            ->create();

        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('getAuthGuard')
            ->andReturn(GuardEnum::DEFAULT->value);

        /** @var User $user */
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertFalse($this->roleRepository->canAccessPanel($user, $panel));
    }
}
