<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Tests\DatabaseTestCase;

class TeamRepositoryTest extends DatabaseTestCase
{
    protected TeamRepository $teamRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = $this->app->make(TeamRepository::class);
    }

    public function testGetDefaultTeamForUserReturnsOwnTeam(): void
    {
        User::unsetEventDispatcher();
        $user = User::factory()
            ->withOwnTeam()
            ->create();
        $team = $user->teams()->first();

        $this->assertSame($team->getKey(), $this->teamRepository->getDefaultTeamForUser($user)->getKey());
    }

    public function testGetDefaultTeamForUserReturnsNull(): void
    {
        User::unsetEventDispatcher();
        $user = User::factory()
            ->create();

        $this->assertNull($this->teamRepository->getDefaultTeamForUser($user));
    }

    public function testIsUserOwnerOfTeamReturnsTrue(): void
    {
        $user = User::factory()
            ->withOwnTeam()
            ->create();
        $team = $user->teams()->first();
        $this->assertTrue($this->teamRepository->isUserOwnerOfTeam($user, $team));
    }

    public function testIsUserOwnerOfTeamReturnsFalse(): void
    {
        $user = User::factory()
            ->create();
        $team = Team::factory()->create();
        $this->assertFalse($this->teamRepository->isUserOwnerOfTeam($user, $team));
    }

    public function testCreateOwnTeamReturnsCorrectTeam(): void
    {
        $user = User::withoutEvents(static function () {
            return User::factory()
                ->create();
        });
        $newTeam = $this->teamRepository->createOwnTeamForUser($user);
        $this->assertTrue($newTeam->exists);
        $team = $user->teams()->latest()->first();

        $this->assertSame($team->getKey(), $newTeam->getKey());
    }
}