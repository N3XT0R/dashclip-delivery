<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Permission;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\DatabaseTestCase;

final class PermissionTest extends DatabaseTestCase
{
    public function testExtendsSpatiePermissionAndPersists(): void
    {
        $permission = Permission::create(['name' => 'do something', 'guard_name' => 'web']);

        $this->assertInstanceOf(SpatiePermission::class, $permission);
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'do something',
        ]);
    }
}
