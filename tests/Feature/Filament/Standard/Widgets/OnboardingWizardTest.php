<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Widgets;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Widgets\OnboardingWizard;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class OnboardingWizardTest extends DatabaseTestCase
{
    private User $user;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.defaults.guard', GuardEnum::STANDARD->value);

        $this->user = User::factory()
            ->withOwnTeam()
            ->standard(GuardEnum::STANDARD)
            ->create([
                'onboarding_completed' => false,
            ]);

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    public function testCanViewReturnsTrueForIncompleteOnboarding(): void
    {
        $this->assertTrue(OnboardingWizard::canView());
    }

    public function testCanViewReturnsFalseAfterCompletion(): void
    {
        $this->user->forceFill([
            'onboarding_completed' => true,
        ])->save();

        $this->assertFalse(OnboardingWizard::canView());
    }

    public function testRendersStaticCtaLinkWithoutDispatches(): void
    {
        $component = Livewire::test(OnboardingWizard::class);

        $dispatches = data_get($component->effects, 'dispatches', []);

        $this->assertSame([], $dispatches);

        $component
            ->assertSee('Onboarding starten')
            ->assertSee(route('filament.standard.pages.onboarding', ['tenant' => $this->tenant->getKey()]));
    }
}
