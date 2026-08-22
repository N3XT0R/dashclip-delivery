<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use App\Models\Team;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\DatabaseTestCase;

final class TeamEndpointsTest extends DatabaseTestCase
{
    private function actingUser(array $scopes): User
    {
        $user = User::factory()->withOwnTeam()->standard()->create();
        Passport::actingAs($user, $scopes);

        return $user;
    }

    public function testIndexShowsOwnAndMemberTeamsOnly(): void
    {
        $user = $this->actingUser(['teams:read']);
        $memberTeam = Team::factory()->create();
        $memberTeam->users()->attach($user->getKey());
        Team::factory()->create();

        $ids = $this->getJson('/api/v1/teams')->assertOk()->json('data.*.id');
        $ownTeamId = Team::query()->where('owner_id', $user->getKey())->firstOrFail()->getKey();
        $this->assertContains($memberTeam->getKey(), $ids);
        $this->assertContains($ownTeamId, $ids);
        $this->assertCount(2, $ids);
    }

    public function testStoreCreatesTeamOwnedByUser(): void
    {
        $user = $this->actingUser(['teams:write']);

        $response = $this->postJson('/api/v1/teams', ['name' => 'API Crew']);

        $response->assertCreated()->assertJsonPath('data.name', 'API Crew');
        $this->assertSame($user->getKey(), Team::query()->findOrFail($response->json('data.id'))->owner_id);
    }

    public function testDestroyByNonOwnerReturns404(): void
    {
        $user = $this->actingUser(['teams:delete']);
        $memberTeam = Team::factory()->create();
        $memberTeam->users()->attach($user->getKey());

        $this->deleteJson('/api/v1/teams/' . $memberTeam->getKey())->assertNotFound();
    }

    public function testDestroyOwnTeamReturns204(): void
    {
        $user = $this->actingUser(['teams:delete']);
        $team = Team::factory()->create(['owner_id' => $user->getKey()]);

        $this->deleteJson('/api/v1/teams/' . $team->getKey())->assertNoContent();
        $this->assertNull(Team::query()->find($team->getKey()));
    }

    public function testShowWithNonNumericIdReturns404(): void
    {
        $this->actingUser(['teams:read']);

        $this->getJson('/api/v1/teams/abc')->assertNotFound();
    }
}
