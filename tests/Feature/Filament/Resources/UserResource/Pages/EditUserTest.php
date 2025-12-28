<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditUserTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testUpdatingWithoutPasswordKeepsExistingHash(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('original-password'),
        ]);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->assertStatus(200)
            ->fillForm([
                'name' => 'Updated Name',
                'email' => $user->email,
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    public function testUpdatingWithPasswordRehashesValue(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('original-password'),
        ]);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'new-password-123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function testEditUserPageShowsDeleteAction(): void
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->assertStatus(200)
            ->assertActionVisible('delete');
    }
}
