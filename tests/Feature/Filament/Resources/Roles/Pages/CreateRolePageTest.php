<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Models\Role;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class CreateRolePageTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testCreateRoleStoresRoleAndPermissions(): void
    {
        $roleName = 'content_reviewer';

        Livewire::test(CreateRole::class)
            ->set('data.name', $roleName)
            ->set('data.guard_name', 'web')
            ->set('data.page_permissions', ['content.review', 'content.publish'])
            ->set('data.select_all', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Utils::getRoleModel(), [
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas(Permission::class, [
            'name' => 'content.review',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas(Permission::class, [
            'name' => 'content.publish',
            'guard_name' => 'web',
        ]);

        $role = Role::query()->where('name', $roleName)->firstOrFail();

        $this->assertTrue($role->hasPermissionTo('content.review'));
        $this->assertTrue($role->hasPermissionTo('content.publish'));
    }
}
