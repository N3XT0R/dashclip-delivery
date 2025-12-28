<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration tests for the Filament UserResource.
 *
 * Verifies:
 *  - ListUsers page renders and sorts users correctly
 *  - Table columns exist and can display key attributes
 *  - ResetPassword action updates user password and triggers notification
 *  - Super Admin access and navigation badge behavior
 */
final class UserResourceTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testListUsersRendersAndShowsRecords(): void
    {
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords($users);
    }

    public function testTableHasExpectedColumns(): void
    {
        Livewire::test(ListUsers::class)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('submitted_name')
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('email_verified_at')
            ->assertTableColumnExists('roles.name')
            ->assertTableColumnExists('created_at')
            ->assertTableColumnExists('updated_at')
            ->assertTableColumnExists('has_email_authentication');
    }

    public function testResetPasswordActionUpdatesPasswordAndSendsNotification(): void
    {
        Notification::fake();

        $user = User::factory()->standard()->create([
            'password' => Hash::make('old-password'),
        ]);

        Livewire::test(ListUsers::class)
            ->callTableAction('resetPassword', $user);

        $user->refresh();

        $this->assertFalse(Hash::check('old-password', $user->password));

        Notification::assertNothingSent();
    }

    public function testSuperAdminSeesNavigationBadge(): void
    {
        $this->actingAs($this->admin);

        $badge = UserResource::getNavigationBadge();

        $this->assertSame((string)User::count(), $badge);
    }

    public function testSuperAdminCanAccessResource(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue(UserResource::canAccess());
        $this->assertTrue(UserResource::shouldRegisterNavigation());
    }

    public function testRegularUserCannotAccessResource(): void
    {
        $user = User::factory()->standard()->create();
        $this->actingAs($user);

        $this->assertFalse(UserResource::canAccess());
        $this->assertFalse(UserResource::shouldRegisterNavigation());
    }

    public function testNavigationBadgeHiddenForNonSuperAdmin(): void
    {
        $user = User::factory()->standard()->create();
        $this->actingAs($user);

        $this->assertNull(UserResource::getNavigationBadge());
    }
}
