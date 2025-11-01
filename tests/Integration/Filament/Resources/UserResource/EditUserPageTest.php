<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\UserResource;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration test for the Filament EditUser page.
 *
 * Verifies:
 *  - Edit page renders correctly for SuperAdmins
 *  - DeleteAction is visible in header actions
 *  - Regular users cannot access the edit page
 */
final class EditUserPageTest extends DatabaseTestCase
{
    private User $admin;
    private User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->regular = User::factory()->standard()->create();
    }

    public function testEditUserPageRendersForAdminAndShowsDeleteAction(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->assertStatus(200)
            ->assertActionVisible('delete');
    }

    public function testRegularUserCannotAccessEditPage(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->regular);

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->assertForbidden();
    }
}
