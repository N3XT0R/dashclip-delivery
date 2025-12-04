<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Models\Team;
use App\Models\User;
use Filament\Panel;
use Tests\DatabaseTestCase;

/**
 * Integration tests for the User model.
 *
 * Verifies:
 * - display_name accessor logic
 * - persistence of encrypted app authentication fields
 * - email authentication toggling
 * - Filament panel access behavior
 */
final class UserTest extends DatabaseTestCase
{
    public function testResolvesDisplayNameFromSubmittedNameFirst(): void
    {
        $user = User::factory()->create([
            'name' => 'Fallback Name',
            'submitted_name' => 'Submitted Name',
        ]);

        $this->assertSame('Submitted Name', $user->display_name);
    }

    public function testResolvesDisplayNameFromNameWhenSubmittedNameMissing(): void
    {
        $user = User::factory()->create([
            'name' => 'Only Name',
            'submitted_name' => null,
        ]);

        $this->assertSame('Only Name', $user->display_name);
    }

    public function testCanStoreAndRetrieveAppAuthenticationSecret(): void
    {
        $user = User::factory()->create();
        $secret = 'secret-key-123';

        $user->saveAppAuthenticationSecret($secret);

        $this->assertSame($secret, $user->fresh()->getAppAuthenticationSecret());
    }

    public function testCanStoreAndRetrieveAppAuthenticationRecoveryCodes(): void
    {
        $user = User::factory()->create();
        $codes = ['abc', 'def', 'ghi'];

        $user->saveAppAuthenticationRecoveryCodes($codes);

        $this->assertSame($codes, $user->fresh()->getAppAuthenticationRecoveryCodes());
    }

    public function testCanToggleEmailAuthentication(): void
    {
        $user = User::factory()->create(['has_email_authentication' => false]);

        $user->toggleEmailAuthentication(true);
        $this->assertTrue($user->fresh()->hasEmailAuthentication());

        $user->toggleEmailAuthentication(false);
        $this->assertFalse($user->fresh()->hasEmailAuthentication());
    }

    public function testReturnsEmailAsAppAuthenticationHolderName(): void
    {
        $user = User::factory()->create(['email' => 'tester@example.com']);

        $this->assertSame('tester@example.com', $user->getAppAuthenticationHolderName());
    }

    public function testRegularCanAccessPanelReturnsTrue(): void
    {
        $user = User::factory()->standard(GuardEnum::DEFAULT)->create();
        $panel = Panel::make()
            ->id(PanelEnum::STANDARD->value);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function testRegularCanAccessPanelAdminReturnsFalse(): void
    {
        $user = User::factory()->standard()->create();

        $panel = Panel::make()
            ->id(PanelEnum::ADMIN->value);

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function testAdminCanAccessPanelAlwaysReturnsTrue(): void
    {
        $user = User::factory()->admin()->create();

        $panel = Panel::make()
            ->id('irgendwas');

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function testCanAccessTenantReturnsTrue(): void
    {
        $user = User::factory()
            ->withOwnTeam()
            ->create();

        $this->assertTrue($user->canAccessTenant($user->teams()->first()));
    }

    public function testCanAccessTenantReturnsFalse(): void
    {
        $user = User::factory()
            ->create();
        $team = Team::factory()->create();

        $this->assertFalse($user->canAccessTenant($team));
    }


    public function testGetDefaultTenantReturnsOwnTeam(): void
    {
        $user = User::factory()
            ->withOwnTeam()
            ->create();

        $panel = Panel::make()
            ->id(PanelEnum::STANDARD->value);

        $this->assertSame(
            $user->teams()->first()->getKey(),
            $user->getDefaultTenant($panel)->getKey()
        );
    }

    public function testGetDefaultTenantReturnsNull(): void
    {
        User::unsetEventDispatcher();
        $user = User::factory()
            ->create();

        $panel = Panel::make()
            ->id(PanelEnum::STANDARD->value);

        $this->assertNull($user->getDefaultTenant($panel));
    }


    public function testScopeReturnsOwnTeam(): void
    {
        User::unsetEventDispatcher();
        $user = User::factory()
            ->withOwnTeam()
            ->create();
        $user->refresh();
        $team = $user->teams()->first();

        $gotTeam = User::query()->isOwnTeam($user)->first();

        $this->assertSame($team->getKey(), $gotTeam->getKey());
    }

    public function testGetTenantsReturnsAllTeams(): void
    {
        $user = User::factory()->create();
        $existingTeamIds = $user->teams()->pluck('teams.id');
        $teams = Team::factory()->count(2)->create();
        $user->teams()->attach($teams->pluck('id')->all());

        $panel = Panel::make()->id(PanelEnum::STANDARD->value);

        $tenants = $user->getTenants($panel);

        $this->assertCount($existingTeamIds->count() + $teams->count(), $tenants);
        $this->assertEqualsCanonicalizing(
            $existingTeamIds->merge($teams->pluck('id'))->unique()->all(),
            $tenants->pluck('id')->all()
        );
    }

    public function testTeamRolesMorphToManyReturnsAssignedRoles(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $role = \App\Models\Role::factory()->create();

        $user->teamRoles()->attach($role->getKey());

        $roles = $user->teamRoles;

        $this->assertTrue($roles->contains(fn($assignedRole) => $assignedRole->is($role)));
        $this->assertSame(
            $user->getKey(),
            (int) $roles->firstWhere('id', $role->getKey())->pivot->model_id
        );
    }

    public function testMailConfigsReturnsConfigsForUser(): void
    {
        $user = User::factory()->create();
        $configs = \App\Models\UserMailConfig::factory()->count(2)->forUser($user)->create();

        $this->assertCount(2, $user->mailConfigs);
        $this->assertEqualsCanonicalizing($configs->pluck('key')->all(), $user->mailConfigs->pluck('key')->all());
    }
}
