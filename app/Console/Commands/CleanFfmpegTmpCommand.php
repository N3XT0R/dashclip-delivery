<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanFfmpegTmpCommand extends Command
{
    protected $signature = 'clean:ffmpeg-tmp {--older-than=2 : Delete files older than this many hours}';

    protected $description = 'Remove stale FFmpeg temporary files from storage/app/ffmpeg-tmp';

    public function handle(): int
    {
        $dir = storage_path('app/ffmpeg-tmp');

        if (!is_dir($dir)) {
            return self::SUCCESS;
        }

        $olderThan = (int) $this->option('older-than');
        $cutoff = now()->subHours($olderThan)->getTimestamp();
        $deleted = 0;

        foreach (File::files($dir) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} stale FFmpeg temp file(s).");
        }

        return self::SUCCESS;
    }
}
