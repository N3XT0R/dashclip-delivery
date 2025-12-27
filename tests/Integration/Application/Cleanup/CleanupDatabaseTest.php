<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Cleanup;

use App\Application\Cleanup\CleanupDatabase;
use App\Repository\ActionTokenRepository;
use App\Models\ActionToken;
use App\Models\User;
use Tests\DatabaseTestCase;

final class CleanupDatabaseTest extends DatabaseTestCase
{
    public function testHandleCleansExpiredAndOrphanTokens(): void
    {
        $validToken = ActionToken::factory()->create([
            'expires_at' => now()->addDay(),
        ]);

        $expiredToken = ActionToken::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $user = User::factory()->create();
        $orphanToken = ActionToken::factory()
            ->for($user, 'subject')
            ->create();

        $user->delete();

        $cleanup = new CleanupDatabase(app(ActionTokenRepository::class));
        $cleanup->handle();

        $this->assertDatabaseMissing(ActionToken::class, ['id' => $expiredToken->id]);
        $this->assertDatabaseMissing(ActionToken::class, ['id' => $orphanToken->id]);
        $this->assertDatabaseHas(ActionToken::class, ['id' => $validToken->id]);
    }
}
