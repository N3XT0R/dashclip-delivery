<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Pages\Auth;

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration test for the Filament EditProfile page.
 *
 * Verifies:
 *  - The page renders correctly for authenticated standard users
 *  - Guests are redirected to the login page
 *  - Profile data can be updated successfully
 */
final class EditProfilePageTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a standard user with known credentials
        $this->user = User::factory()->standard()->create([
            'name' => 'Original Name',
            'submitted_name' => 'Original Submitted',
            'email' => 'original@example.com',
            'password' => Hash::make('old-password'),
        ]);
    }

    public function testEditProfilePageRendersForAuthenticatedUser(): void
    {
        $this->actingAs($this->user);

        Livewire::test(EditProfile::class)
            ->assertSuccessful()
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('submitted_name')
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('password')
            ->assertFormFieldExists('passwordConfirmation');
    }

    public function testGuestIsRedirectedToLogin(): void
    {
        // In Filament v4, guests are redirected automatically to the configured auth route
        $response = $this->get(route('filament.admin.auth.profile'));

        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    public function testUserCanUpdateProfile(): void
    {
        $this->actingAs($this->user);

        Livewire::test(EditProfile::class)
            ->fillForm([
                'name' => 'Updated Name',
                'submitted_name' => 'Updated Submitted',
                'email' => 'updated@example.com',
                'password' => 'new-password-123',
                'passwordConfirmation' => 'new-password-123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Verify that the user record has been updated
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'submitted_name' => 'Updated Submitted',
            'email' => 'original@example.com',
        ]);

        // Verify that the password was rehashed and stored correctly
        $this->assertTrue(
            Hash::check('new-password-123', $this->user->fresh()->password),
            'Expected password to be hashed correctly in the database.'
        );
    }
}
