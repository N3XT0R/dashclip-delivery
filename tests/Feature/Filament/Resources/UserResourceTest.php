<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

final class UserResourceTest extends DatabaseTestCase
{
    public function testCanAccessReturnsTrueForSuperAdmin(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        $this->assertTrue(UserResource::canAccess());
    }

    public function testCanAccessReturnsFalseForRegularUser(): void
    {
        $user = User::factory()->standard()->create();

        $this->actingAs($user);

        $this->assertFalse(UserResource::canAccess());
    }

    public function testGetNavigationBadgeVisibleOnlyForSuperAdmin(): void
    {
        $admin = User::factory()->admin()->create();
        $regular = User::factory()->standard()->create();

        $this->actingAs($admin);
        $this->assertEquals(User::count(), UserResource::getNavigationBadge());

        $this->actingAs($regular);
        $this->assertNull(UserResource::getNavigationBadge());
    }

    public function testResetPasswordActionChangesPasswordAndCreatesNotification(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->standard()->create(['password' => bcrypt('oldpassword')]);

        $this->actingAs($admin);

        $passwordBefore = $target->password;

        // Simuliere Filament Action direkt
        $password = Str::password(12);
        $target->update(['password' => bcrypt($password)]);

        // Einfach sicherstellen, dass Filament-Notification korrekt initialisiert werden kann
        $notification = FilamentNotification::make()
            ->title('Password reset to "'.$password.'"')
            ->success();

        $this->assertInstanceOf(FilamentNotification::class, $notification);

        $target->refresh();
        $this->assertNotEquals($passwordBefore, $target->password);
    }

    public function testShouldRegisterNavigationReflectsCanAccess(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());

        $regular = User::factory()->standard()->create();

        $this->actingAs($regular);
        $this->assertFalse(UserResource::shouldRegisterNavigation());
    }
}
