<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Contracts\UnzipServiceInterface;
use App\Services\Zip\Dto\UnzipStats;

final class FakeUnzipService implements UnzipServiceInterface
{
    public int $extractSingleCallCount = 0;
    private bool $extractSingleResult = true;

    public function withExtractResult(bool $result): static
    {
        $this->extractSingleResult = $result;
        return $this;
    }

    public function extractSingle(string $absoluteZipPath, string $absoluteTargetDir): bool
    {
        $this->extractSingleCallCount++;
        return $this->extractSingleResult;
    }

    public function unzipDirectory(string $directory): UnzipStats
    {
        return new UnzipStats([], [], []);
    }
}
