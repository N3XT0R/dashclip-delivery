<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ListUsersTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testListUsersHeaderShowsCreateAction(): void
    {
        Livewire::test(ListUsers::class)
            ->assertStatus(200)
            ->assertActionVisible('create');
    }

    public function testListUsersShowsRecordActions(): void
    {
        $user = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertStatus(200)
            ->assertTableActionVisible('edit', $user)
            ->assertTableActionVisible('resetPassword', $user);
    }
}
