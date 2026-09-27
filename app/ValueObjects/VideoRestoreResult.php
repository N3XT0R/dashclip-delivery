<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Outcome of one run that takes back deletions the distribution marked too early.
 */
final readonly class VideoRestoreResult
{
    /**
     * @param int $restored videos that are available again
     * @param int $skipped videos that matched but whose files were already removed for good
     */
    public function __construct(public int $restored, public int $skipped)
    {
    }
}
