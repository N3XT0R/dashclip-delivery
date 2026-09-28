<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Team\TeamSettingEnum;
use App\Filament\Standard\Pages\Auth\EditTenantProfile;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use App\Repository\TeamSettingRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * The team profile carries the settings a team chooses for itself.
 */
final class TeamSettingsSectionTest extends DatabaseTestCase
{
    private User $user;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withOwnTeam()->admin(GuardEnum::STANDARD)->create();
        $this->team = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->team, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    public function testTheSettingIsOfferedWithItsExplanation(): void
    {
        Livewire::test(EditTenantProfile::class)
            ->assertStatus(200)
            ->assertSee(__('team_settings.title'))
            ->assertSee(__('team_settings.censor_license_plates.label'));
    }

    public function testTheChoiceOfTheTeamIsShown(): void
    {
        app(TeamSettingRepository::class)
            ->set($this->team, TeamSettingEnum::CENSOR_LICENSE_PLATES, true);

        Livewire::test(EditTenantProfile::class)
            ->assertFormSet(['team_settings' => [TeamSettingEnum::CENSOR_LICENSE_PLATES->value => true]]);
    }

    public function testSavingKeepsTheChoice(): void
    {
        Livewire::test(EditTenantProfile::class)
            ->fillForm([
                'name' => $this->team->name,
                'team_settings' => [TeamSettingEnum::CENSOR_LICENSE_PLATES->value => true],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertTrue(
            (bool)app(TeamSettingRepository::class)
                ->get($this->team->refresh(), TeamSettingEnum::CENSOR_LICENSE_PLATES)
        );
    }
}
