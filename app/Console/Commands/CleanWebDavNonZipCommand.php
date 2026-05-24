<?php
declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class CleanWebDavNonZipCommand extends Command
{
    protected $signature = 'clean:webdav-non-zip {--disk=import : Storage disk to clean}';

    protected $description = 'Delete non-ZIP files from WebDAV user directories';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        $storage = Storage::disk($disk);
        $root = config('webdav-server.storage.spaces.default.root', 'webdav');
        $deleted = 0;

        foreach ($storage->directories($root) as $userDir) {
            foreach ($storage->files($userDir) as $file) {
                if (!str_ends_with(strtolower($file), '.zip')) {
                    $storage->delete($file);
                    $deleted++;
                }
            }
        }

        $this->info("Deleted {$deleted} non-ZIP file(s) from WebDAV directories.");

        return self::SUCCESS;
    }
}
