<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Pivots\ModelHasRoleTeam;
use App\Models\Role;
use App\Models\User;
use Tests\DatabaseTestCase;

final class ModelHasRoleTeamTest extends DatabaseTestCase
{
    public function testStoresPivotWithTeamContext(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $pivot = new ModelHasRoleTeam([
            'role_id' => $role->getKey(),
            'model_type' => User::class,
            'model_id' => $user->getKey(),
        ]);
        $pivot->timestamps = false;
        $pivot->save();

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->getKey(),
            'model_id' => $user->getKey(),
        ]);
        $this->assertSame($role->getKey(), $pivot->role_id);
        $this->assertSame($user->getKey(), $pivot->model_id);
    }
}
