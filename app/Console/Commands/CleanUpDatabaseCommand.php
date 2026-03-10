<?php

namespace App\Console\Commands;

use App\Application\Cleanup\CleanupActionTokens;
use App\Application\Cleanup\CleanupTransitionState;
use App\Application\Cleanup\DeleteVideosMissingFromStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanUpDatabaseCommand extends Command
{
    protected $signature = 'clean:database';

    protected $description = 'Clean up the database by removing old or unnecessary records';

    public function handle(): int
    {
        try {
            app(CleanupActionTokens::class)->handle();
            app(CleanupTransitionState::class)->handle();
            app(DeleteVideosMissingFromStorage::class)->handle();
        } catch (\Throwable $e) {
            Log::error('Error during database cleanup: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
