<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Outcome of blurring one video.
 */
final readonly class CensorResult
{
    /**
     * @param string $path where the blurred copy was written
     * @param int $framesLookedAt how many frames the detection ran on
     * @param int $regionsBlurred how many regions were blurred in total
     */
    public function __construct(
        public string $path,
        public int $framesLookedAt = 0,
        public int $regionsBlurred = 0,
    ) {
    }
}
