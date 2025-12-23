<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CleanupService;
use Illuminate\Console\Command;

class CleanUpDiskCommand extends Command
{
    protected $signature = 'clean:disk
    {--disk=uploads : The filesystem disk to clean (as defined in config/filesystems.php)}
    {--days=30 : Delete files older than this many days}';

    public function handle(CleanupService $cleanupService): int
    {
        $disk = (string)($this->option('disk') ?? '');
        $days = (int)($this->option('days') ?? 30);
        $cleanupService->cleanDisk($disk, $days);

        return self::SUCCESS;
    }
}
