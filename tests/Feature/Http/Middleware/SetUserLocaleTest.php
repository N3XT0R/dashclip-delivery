<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;
use Tests\DatabaseTestCase;

final class SetUserLocaleTest extends DatabaseTestCase
{
    public function testAuthenticatedAdminUserLocaleIsAppliedInAdminPanel(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->get(route('filament.admin.auth.profile'))
            ->assertOk();

        $this->assertSame('en', App::getLocale());
    }

    public function testAuthenticatedStandardUserLocaleIsAppliedInStandardPanel(): void
    {
        $user = User::factory()->withOwnTeam()->standard(GuardEnum::STANDARD)->create(['locale' => 'en']);
        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($tenant, true);

        $this->actingAs($user, GuardEnum::STANDARD->value)
            ->get(route('filament.standard.auth.profile'))
            ->assertOk();

        $this->assertSame('en', App::getLocale());
    }

    public function testGuestRequestLeavesDefaultLocaleUnchanged(): void
    {
        $this->get(route('filament.admin.auth.profile'));

        $this->assertSame(config('app.locale'), App::getLocale());
    }
}
