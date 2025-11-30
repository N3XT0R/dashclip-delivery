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

    public function testSubmitCompletesOnboardingAndStoresNotification(): void
    {
        Livewire::test(OnboardingWizard::class)
            ->call('submit');

        $this->assertTrue($this->user->fresh()->onboarding_completed);
        $notifications = session('filament.notifications', []);

        $this->assertNotEmpty($notifications);
        $this->assertSame('Onboarding abgeschlossen', $notifications[0]['title']);
    }
}
