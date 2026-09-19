<?php

declare(strict_types=1);

namespace App\Services\Storage;

/**
 * Answers questions about configured storage disks without connecting to them.
 */
final readonly class StorageDiskService
{
    public function isConfigured(string $disk): bool
    {
        return is_array(config("filesystems.disks.$disk"));
    }

    /** Local disks can be read directly from the file system; every other driver is remote. */
    public function isLocal(string $disk): bool
    {
        return config("filesystems.disks.$disk.driver") === 'local';
    }
}
