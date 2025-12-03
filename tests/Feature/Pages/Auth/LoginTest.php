<?php

declare(strict_types=1);

namespace Tests\Feature\Pages\Auth;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Pages\Auth\Login;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\DatabaseTestCase;

final class LoginTest extends DatabaseTestCase
{
    public function testRegularPanelUserCanAuthenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'panel-user@example.test',
        ]);

        $webRole = Role::firstOrCreate([
            'name' => RoleEnum::REGULAR->value,
            'guard_name' => GuardEnum::DEFAULT->value,
        ]);

        $standardRole = Role::firstOrCreate([
            'name' => RoleEnum::REGULAR->value,
            'guard_name' => GuardEnum::STANDARD->value,
        ]);

        $user->syncRoles([$webRole, $standardRole]);

        Filament::setCurrentPanel(Filament::getPanel(PanelEnum::ADMIN->value));

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user, GuardEnum::DEFAULT->value);
    }

    public function testNonPanelUserIsLoggedOutAndSeesValidationError(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin-user@example.test',
        ]);

        Filament::setCurrentPanel(Filament::getPanel(PanelEnum::ADMIN->value));

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest(GuardEnum::DEFAULT->value);

        $activity = Activity::query()->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame('login', $activity->event);
        $this->assertSame($user->getMorphClass(), $activity->causer_type);
        $this->assertSame($user->getKey(), $activity->causer_id);
        $this->assertSame(PanelEnum::ADMIN->value, $activity->getExtraProperty('panel'));
        $this->assertSame(request()->ip(), $activity->getExtraProperty('ip'));
    }
}
