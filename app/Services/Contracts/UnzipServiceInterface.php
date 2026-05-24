<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Exceptions\IO\FileNotFoundException;
use App\Services\Zip\Dto\UnzipStats;

interface UnzipServiceInterface
{
    /**
     * Unzip all .zip files found directly under the given directory.
     * Returns a stats DTO with per-archive results.
     *
     * @throws \InvalidArgumentException if the directory does not exist or is not readable
     */
    public function unzipDirectory(string $directory): UnzipStats;

    /**
     * Extract a single ZIP archive into $absoluteTargetDir, then delete the archive.
     * Returns true on success, false if the archive cannot be opened, extracted, or contains no safe entries.
     *
     * @throws FileNotFoundException if $absoluteZipPath does not exist
     */
    public function extractSingle(string $absoluteZipPath, string $absoluteTargetDir): bool;
}