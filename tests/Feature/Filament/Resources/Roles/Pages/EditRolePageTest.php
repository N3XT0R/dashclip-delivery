<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\Pages\EditRole;
use App\Models\Role;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class EditRolePageTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testEditRoleUpdatesPermissions(): void
    {
        $role = Role::query()->create([
            'name' => 'editor',
            'guard_name' => 'web',
        ]);

        $existingPermission = Permission::query()->create([
            'name' => 'content.review',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($existingPermission);

        Livewire::test(EditRole::class, [
            'record' => $role->getKey(),
        ])
            ->set('data.guard_name', 'web')
            ->set('data.module_permissions', ['content.publish'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($role->fresh()->hasPermissionTo('content.publish'));
        $this->assertFalse($role->fresh()->hasPermissionTo('content.review'));
    }

    public function testDisabledFieldsRetainStoredValues(): void
    {
        $role = Role::query()->create([
            'name' => 'auditor',
            'guard_name' => 'web',
        ]);

        Livewire::test(EditRole::class, [
            'record' => $role->getKey(),
        ])
            ->call('save')
            ->assertHasNoErrors();

        $updated = Role::query()->findOrFail($role->getKey());

        $this->assertSame('auditor', $updated->name);
        $this->assertSame('web', $updated->guard_name);
        $this->assertDatabaseHas(Utils::getRoleModel(), [
            'id' => $role->getKey(),
            'guard_name' => 'web',
        ]);
    }
}
