<?php

declare(strict_types=1);

namespace App\Services\Zip;

/**
 * What one archive build needs to know while it packs: which job it reports to, how far it has
 * come and which temporary copies it has to clean up afterwards.
 */
final class ZipBuildContext
{
    /**
     * @var array<int, string> temporary copies of remote videos, removed when the build ends
     */
    private array $temporaryFiles = [];

    private int $processed = 0;

    /**
     * @param string $jobId the download job this build reports its progress to
     * @param int $total number of offers in this archive
     */
    public function __construct(public readonly string $jobId, public readonly int $total)
    {
    }

    public function processed(): int
    {
        return $this->processed;
    }

    public function advance(): void
    {
        $this->processed++;
    }

    public function rememberTemporaryFile(string $path): void
    {
        $this->temporaryFiles[] = $path;
    }

    /**
     * @return array<int, string>
     */
    public function temporaryFiles(): array
    {
        return $this->temporaryFiles;
    }
}
