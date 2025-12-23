<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;

class FilesystemService
{
    public function getDiskPath(string $disk): string
    {
        $disks = config('filesystems.disks');

        if (!isset($disks[$disk])) {
            throw new InvalidArgumentException("Disk '{$disk}' is not defined in filesystems configuration.");
        }

        return $disks[$disk]['root'] ?? '';
    }

    public function getFilesFromDisk(Filesystem $disk, ?string $directory = null): array
    {
        return $disk->allFiles($directory);
    }

    public function getFilesOlderThan(Filesystem $disk, int $days, ?string $directory = null): array
    {
        $files = $this->getFilesFromDisk($disk, $directory);
        $threshold = now()->subDays($days)->timestamp;
        $oldFiles = [];

        foreach ($files as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $oldFiles[] = $file;
            }
        }

        return $oldFiles;
    }

    public function deleteFiles(Filesystem $disk, array $files): int
    {
        $deletedCount = 0;

        foreach ($files as $file) {
            if ($disk->delete($file)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
