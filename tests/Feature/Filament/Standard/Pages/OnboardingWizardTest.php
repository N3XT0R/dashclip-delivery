<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Pages\OnboardingWizard;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class OnboardingWizardTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $guard = GuardEnum::STANDARD;

        $this->user = User::factory()
            ->withOwnTeam()
            ->standard($guard)
            ->create([
                'onboarding_completed' => false,
            ]);

        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, $guard->value);
    }

    public function testPageLoadsWizard(): void
    {
        Livewire::test(OnboardingWizard::class)
            ->assertStatus(200)
            ->assertSee('Willkommen im Standard-Panel');
    }

    public function testSubmitCompletesOnboardingAndRedirectsToDashboard(): void
    {
        Livewire::test(OnboardingWizard::class)
            ->call('submit')
            ->assertRedirect(route('filament.standard.pages.dashboard', [
                'tenant' => Filament::getTenant(),
            ]));

        $this->assertTrue($this->user->fresh()->onboarding_completed);
    }
}
