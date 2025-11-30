<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ViewRolePageTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testViewRolePageShowsEditAction(): void
    {
        $role = Role::factory()->create();

        Livewire::test(ViewRole::class, [
            'record' => $role->getKey(),
        ])
            ->assertStatus(200)
            ->assertActionVisible('edit');
    }
}
