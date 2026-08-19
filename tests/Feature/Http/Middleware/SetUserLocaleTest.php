<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Http\Middleware\SetUserLocale;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
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

        $defaultGuard = config('auth.defaults.guard');

        $this->actingAs($user, GuardEnum::STANDARD->value);
        $this->app['auth']->shouldUse($defaultGuard);

        $this->get(route('filament.standard.auth.profile'))
            ->assertOk();

        $this->assertSame('en', App::getLocale());
    }

    /**
     * A regression test targeting the middleware's guard-selection logic in isolation.
     *
     * A full HTTP request to an authenticated panel route cannot be used to distinguish
     * old ($request->user(), default-guard) from new (Filament::auth()->user(), panel-guard)
     * behaviour here: Filament's own Authenticate middleware implements
     * Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests, so Laravel's middleware
     * priority sorting (Kernel::$middlewarePriority) always runs it before SetUserLocale
     * on every authenticated route — and Authenticate itself calls
     * `$this->auth->shouldUse(Filament::getAuthGuard())`, which coincidentally "fixes" the
     * default guard before SetUserLocale ever runs. That masks the bug for full-page loads
     * (confirmed by inspecting the Illuminate\Routing\SortedMiddleware-resolved order for
     * this route). The bug is real on paths that don't go through Filament's Authenticate,
     * e.g. Livewire's persistent-middleware AJAX pipeline — hence this direct test of the
     * middleware's handle() method, which reproduces the divergence deterministically
     * without depending on framework-internal middleware ordering.
     */
    public function testMiddlewareResolvesUserViaPanelGuardNotDefaultGuard(): void
    {
        $user = User::factory()->withOwnTeam()->standard(GuardEnum::STANDARD)->create(['locale' => 'en']);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);

        // Authenticate only on the 'standard' guard, leaving the application's default
        // guard untouched ('web') — this is the state Livewire's AJAX pipeline is in,
        // since it never goes through Filament's Authenticate middleware.
        Auth::guard(GuardEnum::STANDARD->value)->setUser($user);

        $this->assertSame('web', config('auth.defaults.guard'));

        $request = Request::create('/standard/profile', 'GET');

        (new SetUserLocale())->handle($request, fn () => new Response());

        $this->assertSame('en', App::getLocale());
    }

    public function testGuestRequestLeavesDefaultLocaleUnchanged(): void
    {
        $this->get(route('filament.admin.auth.profile'));

        $this->assertSame('de', App::getLocale());
    }

    public function testSetUserLocaleIsRegisteredAsPersistentMiddleware(): void
    {
        $this->assertContains(SetUserLocale::class, Livewire::getPersistentMiddleware());
    }

    public function testStaleLocaleValueFallsBackToDefault(): void
    {
        $user = User::factory()->admin()->create(['locale' => 'fr']); // 'fr' does not exist under lang/

        $this->actingAs($user)
            ->get(route('filament.admin.auth.profile'))
            ->assertOk();

        $this->assertSame('de', App::getLocale());
    }
}
