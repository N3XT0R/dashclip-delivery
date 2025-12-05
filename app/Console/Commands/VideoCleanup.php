<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\VideoCleanupService;
use Illuminate\Console\Command;

class VideoCleanup extends Command
{
    protected $signature = 'video:cleanup {--weeks=1 : Number of weeks the expiration must be exceeded}';

    protected $description = 'Deletes downloaded videos whose expiration date has been exceeded by the specified number of weeks.';


    public function __construct(private VideoCleanupService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $weeks = (int)$this->option('weeks');
        $deleted = $this->service->cleanup($weeks);
        $this->info("Removed: {$deleted}");

        return self::SUCCESS;
    }
}

