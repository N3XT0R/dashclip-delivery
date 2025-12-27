<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Cleanup;

use App\Application\Cleanup\CleanupDatabase;
use App\Repository\ActionTokenRepository;
use Tests\DatabaseTestCase;

final class CleanupDatabaseTest extends DatabaseTestCase
{
    public function testHandleCleansExpiredAndOrphanTokens(): void
    {
        $actionTokenRepository = $this->createMock(ActionTokenRepository::class);
        $actionTokenRepository->expects($this->once())
            ->method('deleteExpired');
        $actionTokenRepository->expects($this->once())
            ->method('deleteOrphans');

        $cleanup = new CleanupDatabase($actionTokenRepository);
        $cleanup->handle();
    }
}
