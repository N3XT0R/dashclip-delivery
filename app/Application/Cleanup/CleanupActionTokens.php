<?php

declare(strict_types=1);

namespace App\Application\Cleanup;

use App\Services\ActionTokenService;

class CleanupActionTokens
{
    public function __construct(
        private ActionTokenService $actionTokenService
    ) {
    }

    public function handle(): void
    {
        $this->actionTokenService->deleteExpired();
        $this->actionTokenService->deleteOrphans();
    }
}
