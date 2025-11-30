<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Tests\DatabaseTestCase;

final class RoleTest extends DatabaseTestCase
{
    public function testBelongsToManyTeamsViaPivot(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $team = Team::factory()->forUser($user)->create();

        $role->teams()->attach($team->getKey(), ['user_id' => $user->getKey()]);

        $attachedTeam = $role->teams()->first();

        $this->assertTrue($attachedTeam->is($team));
        $this->assertDatabaseHas('team_user', [
            'role_id' => $role->getKey(),
            'team_id' => $team->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }
}
