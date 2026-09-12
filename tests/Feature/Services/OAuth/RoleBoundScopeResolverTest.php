<?php

declare(strict_types=1);

namespace Tests\Feature\Services\OAuth;

use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Models\User;
use App\Services\OAuth\RoleBoundScopeResolver;
use Tests\DatabaseTestCase;

final class RoleBoundScopeResolverTest extends DatabaseTestCase
{
    public function testTheMapIsDerivedFromTheApiRoutesThemselves(): void
    {
        $map = app(RoleBoundScopeResolver::class)->scopePermissionMap();

        $this->assertSame(['ManageChannels:Team'], $map->get('channels:read'));
        $this->assertSame(['View:MyOffers'], $map->get('offers:read'));
        $this->assertSame(['ViewAny:Video'], $map->get('videos:read'));
        $this->assertEqualsCanonicalizing(['Create:Video', 'Update:Video'], $map->get('videos:write'));
        $this->assertSame([], $map->get('teams:read'), 'Teams are governed by ownership, not a permission.');
    }

    public function testAChannelOperatorMayOnlyPickChannelAndOfferScopes(): void
    {
        $allowed = $this->allowedFor(RoleEnum::CHANNEL_OPERATOR);

        $this->assertContains('channels:read', $allowed);
        $this->assertContains('offers:write', $allowed);
        $this->assertNotContains('videos:read', $allowed);
        $this->assertNotContains('videos:delete', $allowed);
    }

    public function testARegularUserMayPickVideoScopesButNotOfferScopes(): void
    {
        $allowed = $this->allowedFor(RoleEnum::REGULAR);

        $this->assertContains('videos:read', $allowed);
        $this->assertContains('videos:delete', $allowed);
        $this->assertNotContains('offers:read', $allowed);
    }

    public function testTeamScopesNeedNoPermissionAndAreAlwaysAvailable(): void
    {
        foreach ([RoleEnum::CHANNEL_OPERATOR, RoleEnum::REGULAR] as $role) {
            $this->assertContains('teams:read', $this->allowedFor($role));
        }
    }

    public function testANonUserActorGetsNothing(): void
    {
        $this->assertSame([], app(RoleBoundScopeResolver::class)->allowedScopesFor(null)->all());
    }

    /**
     * @return list<string>
     */
    private function allowedFor(RoleEnum $role): array
    {
        $user = User::factory()->withOwnTeam()->standard()
            ->withRole($role, GuardEnum::STANDARD->value)->create();

        return app(RoleBoundScopeResolver::class)->allowedScopesFor($user)->all();
    }
}
