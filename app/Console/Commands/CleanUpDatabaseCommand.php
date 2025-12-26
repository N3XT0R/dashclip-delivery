<?php

namespace App\Console\Commands;

use App\Services\ActionTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanUpDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up the database by removing old or unnecessary records';

    /**
     * Execute the console command.
     */
    public function handle(ActionTokenService $actionTokenService): int
    {
        try {
            $actionTokenService->cleanupExpired();
        } catch (\Throwable $e) {
            Log::error('Error during database cleanup: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
