<?php

declare(strict_types=1);

namespace Repository;

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

        $this->assertSame($team->getKey(), $this->teamRepository->getDefaultTeamForUser($user));
    }

    public function testGetDefaultTeamForUserReturnsNull(): void
    {
        User::unsetEventDispatcher();
        $user = User::factory()
            ->create();

        $this->assertNull($this->teamRepository->getDefaultTeamForUser($user));
    }
}