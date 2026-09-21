<?php

declare(strict_types=1);

namespace Tests\Integration\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\BlogPermissionSeeder;
use Tests\DatabaseTestCase;

final class BlogPermissionSeederTest extends DatabaseTestCase
{
    private const array MODELS = ['Post', 'PostCategory', 'PostTag'];

    private const array ACTIONS = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Replicate'];

    public function testCreatesEveryEditorialPermissionOnTheWebGuard(): void
    {
        $this->seed(BlogPermissionSeeder::class);

        foreach ($this->expectedPermissionNames() as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function testGrantsEveryEditorialPermissionToTheSuperAdminRole(): void
    {
        $this->seed(BlogPermissionSeeder::class);

        $role = Role::findByName('super_admin', 'web');
        foreach ($this->expectedPermissionNames() as $name) {
            self::assertTrue($role->hasPermissionTo($name, 'web'), $name . ' is missing on super_admin');
        }
    }

    public function testRunningTwiceCreatesNoDuplicates(): void
    {
        $this->seed(BlogPermissionSeeder::class);
        $this->seed(BlogPermissionSeeder::class);

        $count = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->expectedPermissionNames())
            ->count();
        self::assertSame(count($this->expectedPermissionNames()), $count);

        $grants = Role::findByName('super_admin', 'web')
            ->permissions()
            ->whereIn('name', $this->expectedPermissionNames())
            ->count();
        self::assertSame(count($this->expectedPermissionNames()), $grants);
    }

    public function testCreatesThePermissionsWithoutASuperAdminRole(): void
    {
        Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->delete();
        Permission::query()->whereIn('name', $this->expectedPermissionNames())->delete();

        $this->seed(BlogPermissionSeeder::class);

        self::assertSame(
            count($this->expectedPermissionNames()),
            Permission::query()->where('guard_name', 'web')->whereIn('name', $this->expectedPermissionNames())->count(),
        );
    }

    /**
     * @return list<string>
     */
    private function expectedPermissionNames(): array
    {
        $names = [];
        foreach (self::MODELS as $model) {
            foreach (self::ACTIONS as $action) {
                $names[] = $action . ':' . $model;
            }
        }

        return $names;
    }
}
