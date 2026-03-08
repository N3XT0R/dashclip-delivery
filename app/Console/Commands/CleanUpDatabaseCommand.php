<?php

namespace App\Console\Commands;

use App\Application\Cleanup\CleanupDatabase;
use App\Application\Cleanup\CleanupTransitionState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanUpDatabaseCommand extends Command
{
    protected $signature = 'clean:database';

    protected $description = 'Clean up the database by removing old or unnecessary records';

    public function handle(CleanupDatabase $cleanupDatabase): int
    {
        try {
            $cleanupDatabase->handle();
            app(CleanupTransitionState::class)->handle();
        } catch (\Throwable $e) {
            Log::error('Error during database cleanup: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
