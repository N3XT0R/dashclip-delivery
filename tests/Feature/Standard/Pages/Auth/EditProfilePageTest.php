<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages\Auth;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Pages\Auth\EditProfile;
use App\Filament\Standard\Pages\Dashboard;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Verifies that the standard profile page renders inside the full panel layout.
 */
final class EditProfilePageTest extends DatabaseTestCase
{
    public function testProfileRendersInPanelLayoutScopedToOwnTeam(): void
    {
        $user = User::factory()->standard()->create();
        $team = $this->app->make(TeamRepository::class)->getDefaultTeamForUser($user);
        $this->assertNotNull($team);

        $this->actingAs($user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.auth.profile'))
            ->assertOk()
            ->assertSee('fi-sidebar', false)
            ->assertDontSee('fi-simple-layout', false)
            ->assertSee(Dashboard::getUrl(panel: 'standard', tenant: $team), false);
    }

    public function testProfileFallsBackToSimpleLayoutWithoutTeam(): void
    {
        $user = User::factory()->standard()->create();
        $user->teams()->detach();
        Team::query()->where('owner_id', $user->getKey())->delete();

        $this->actingAs($user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.auth.profile'))
            ->assertOk()
            ->assertSee('fi-simple-layout', false)
            ->assertDontSee('fi-sidebar', false);
    }

    public function testProfileCanBeSavedInPanelLayout(): void
    {
        $user = User::factory()->standard()->create(['name' => 'Original Name', 'submitted_name' => 'Original Submitted']);
        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(EditProfile::class)
            ->assertSuccessful()
            ->fillForm(['name' => 'Updated Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Name', $user->refresh()->name);
    }
}
