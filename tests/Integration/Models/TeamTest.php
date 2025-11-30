<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use Tests\DatabaseTestCase;

final class TeamTest extends DatabaseTestCase
{
    public function testRelationshipsAndScopes(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->forUser($owner)->create();
        $member = User::factory()->create();

        $team->users()->attach($member->getKey());

        $activeChannel = Channel::factory()->create();
        $pausedChannel = Channel::factory()->paused()->create();

        $team->assignedChannels()->attach($activeChannel->getKey(), ['quota' => 3]);
        $team->assignedChannels()->attach($pausedChannel->getKey(), ['quota' => 3]);

        $team->refresh();

        $this->assertTrue($team->owner->is($owner));
        $this->assertTrue($team->users->contains($member));
        $this->assertCount(1, $team->assignedChannels);
        $this->assertTrue($team->assignedChannels->first()->is($activeChannel));

        $ownTeams = Team::query()->isOwnTeam($owner)->get();
        $this->assertTrue($ownTeams->contains($team));
    }
}
