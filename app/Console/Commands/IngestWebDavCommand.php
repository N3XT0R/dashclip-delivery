<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessWebDavZipJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class IngestWebDavCommand extends Command
{
    protected $signature = 'ingest:webdav {--disk=import : Storage disk to scan}';

    protected $description = 'Scan all WebDAV user directories for unprocessed ZIP archives and queue them for ingest';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $storage = Storage::disk($disk);
        $root = config('webdav-server.storage.spaces.default.root', 'webdav');
        $queued = 0;

        foreach ($storage->directories($root) as $userDir) {
            foreach ($storage->files($userDir) as $file) {
                if (str_ends_with(strtolower($file), '.zip')) {
                    ProcessWebDavZipJob::dispatch(disk: $disk, path: $file, userId: null);
                    $queued++;
                }
            }
        }

        $this->info("Queued {$queued} ZIP archive(s) for ingest.");

        return self::SUCCESS;
    }
}
