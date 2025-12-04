<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\User;
use App\Models\UserMailConfig;
use Tests\DatabaseTestCase;

final class UserMailConfigTest extends DatabaseTestCase
{
    public function testFactoryPersistsConfigWithBooleanCast(): void
    {
        $config = UserMailConfig::factory()->create([
            'value' => 1,
        ]);

        $this->assertInstanceOf(UserMailConfig::class, $config);
        $this->assertIsBool($config->value);
        $this->assertTrue($config->value);
    }

    public function testBelongsToUserRelation(): void
    {
        $user = User::factory()->create();

        $config = UserMailConfig::factory()
            ->forUser($user)
            ->create([
                'key' => 'daily_summary',
                'value' => false,
            ]);

        $this->assertTrue($config->user->is($user));
        $this->assertSame('daily_summary', $config->key);
        $this->assertFalse($config->value);
    }
}
