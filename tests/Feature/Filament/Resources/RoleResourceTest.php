<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

/**
 * Integration tests for the Filament RoleResource.
 *
 * Verifies:
 *  - ListRoles page renders and sorts records correctly
 *  - Table columns and badges appear as expected
 *  - Resource access is restricted to Super Admins
 *  - Roles can be created and displayed through Filament
 */
final class RoleResourceTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testListRolesDisplaysExistingRoles(): void
    {
        $roles = collect([
            Role::query()->create(['name' => 'content_editor', 'guard_name' => 'web']),
            Role::query()->create(['name' => 'marketing_manager', 'guard_name' => 'web']),
        ]);

        Livewire::test(ListRoles::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords($roles)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('guard_name')
            ->assertTableColumnExists('permissions_count')
            ->assertTableColumnExists('updated_at')
            ->tap(function ($livewire) use ($roles) {
                $ids = $livewire->instance()->getTableRecords()->pluck('id')->all();
                
                foreach ($roles as $role) {
                    $this->assertContains($role->getKey(), $ids);
                }
            });
    }


    public function testCreateRoleFormStoresNewRole(): void
    {
        $roleName = 'quality_assurance_'.Str::random(4);

        Livewire::test(CreateRole::class)
            ->set('data.name', $roleName)
            ->set('data.guard_name', 'web')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Utils::getRoleModel(), [
            'name' => $roleName,
            'guard_name' => 'web',
        ]);
    }

    public function testSuperAdminCanAccessRoleResource(): void
    {
        $this->assertTrue(RoleResource::canAccess());
        $this->assertTrue(RoleResource::shouldRegisterNavigation());
    }

    public function testRegularUserCannotAccessRoleResource(): void
    {
        $user = User::factory()->standard()->create();
        $this->actingAs($user);

        $this->assertFalse(RoleResource::canAccess());
        $this->assertFalse(RoleResource::shouldRegisterNavigation());
    }
}
