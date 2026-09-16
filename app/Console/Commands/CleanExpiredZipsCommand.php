<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredZipsCommand extends Command
{
    protected $signature = 'zips:clean-expired';

    protected $description = 'Remove ZIP archives and temporary copies older than two days';

    /** Retain files beyond the one-day signed download window to allow retries. */
    public function handle(): int
    {
        $disk = Storage::disk();
        $cutoff = now()->subDays(2)->getTimestamp();
        foreach ($disk->allFiles('zips') as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
            }
        }

        return self::SUCCESS;
    }
}
