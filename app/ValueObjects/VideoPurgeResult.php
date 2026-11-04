<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Outcome of one run that removes deleted videos for good.
 */
final readonly class VideoPurgeResult
{
    public function __construct(
        public int $purged,
        public int $failed,
    ) {
    }
}
