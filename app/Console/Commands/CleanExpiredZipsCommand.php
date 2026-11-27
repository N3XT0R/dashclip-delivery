<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Filament\Standard\Exports\OfferExporter;
use Filament\Actions\Exports\Models\Export;

class CleanExpiredZipsCommand extends Command
{
    protected $signature = 'zips:clean-expired';

    protected $description = 'Remove ZIP archives, temporary copies and offer exports that are no longer offered for download';

    /**
     * Retain files beyond the one-day signed download window to allow retries,
     * and remove offer exports once their one-day download period has passed.
     */
    public function handle(): int
    {
        $disk = Storage::disk();
        $cutoff = now()->subDays(2)->getTimestamp();
        foreach ($disk->allFiles('zips') as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
            }
        }

        Export::query()
            ->where('exporter', OfferExporter::class)
            ->where('created_at', '<', now()->subDay())
            ->each(function (Export $export): void {
                $export->deleteFileDirectory();
                $export->delete();
            });

        return self::SUCCESS;
    }
}
