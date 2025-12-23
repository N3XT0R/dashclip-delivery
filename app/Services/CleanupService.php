<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CleanupService
{
    public function __construct(
        protected FilesystemService $filesystemService,
    ) {
    }

    public function cleanDisk(string $disk, int $days): int
    {
        $filesystem = Storage::disk($disk);
        $oldFiles = $this->filesystemService->getFilesOlderThan($filesystem, $days);
        return $this->filesystemService->deleteFiles($filesystem, $oldFiles);
    }
}
