<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Repository\ActionTokenRepository;

final readonly class CleanupDatabase
{
    public function __construct(private ActionTokenRepository $actionTokenRepository)
    {
    }

    public function handle(): void
    {
        $this->actionTokenRepository->deleteExpired();
        $this->actionTokenRepository->deleteOrphans();
    }
}
