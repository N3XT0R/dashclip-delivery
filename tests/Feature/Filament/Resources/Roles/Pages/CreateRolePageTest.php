<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Livewire\Livewire;
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

    public function testCreateRoleStoresRoleWithSelectedGuard(): void
    {
        $roleName = 'content_reviewer';

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
}
