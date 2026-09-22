<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Outcome of one run that marks videos the distribution is done with as deleted.
 */
final readonly class VideoMarkResult
{
    public function __construct(public int $marked)
    {
    }
}
