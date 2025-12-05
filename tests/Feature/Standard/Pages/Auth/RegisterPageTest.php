<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages\Auth;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Pages\Auth\Register;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

final class RegisterPageTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.defaults.guard', GuardEnum::STANDARD->value);

        Filament::setCurrentPanel(Filament::getPanel(PanelEnum::STANDARD->value));
    }

    public function testRegistrationAssignsRegularRoleToNewUser(): void
    {
        Role::findOrCreate(RoleEnum::REGULAR->value, GuardEnum::STANDARD->value);

        $registrationData = [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'secret-password',
            'passwordConfirmation' => 'secret-password',
            'accept_terms' => true,
        ];

        Livewire::test(Register::class)
            ->fillForm($registrationData)
            ->call('register')
            ->assertHasNoFormErrors();

        $user = Filament::auth()->user();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(RoleEnum::REGULAR->value));
    }
}
