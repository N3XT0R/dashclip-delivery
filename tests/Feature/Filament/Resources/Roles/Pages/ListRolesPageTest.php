<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Enum\Guard\GuardEnum;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ListRolesPageTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testListRolesDisplaysTabsPerGuard(): void
    {
        Livewire::test(ListRoles::class)
            ->assertStatus(200)
            ->tap(function ($livewire): void {
                $tabs = $livewire->instance()->getTabs();
                $this->assertSame(array_keys(RoleResource::getGuardOptions()), array_keys($tabs));
            });
    }

    public function testListRolesShowsExistingRecords(): void
    {
        $roles = Role::factory()->forGuard(GuardEnum::DEFAULT)->count(2)->create();

        Livewire::test(ListRoles::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords($roles);
    }
}
