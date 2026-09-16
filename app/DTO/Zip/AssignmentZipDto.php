<?php

declare(strict_types=1);

namespace App\DTO\Zip;

readonly class AssignmentZipDto
{
    /** @param list<int> $assignmentIds */
    public function __construct(
        public ?int $batchId,
        public int $channelId,
        public array $assignmentIds,
        public string $ip,
        public ?string $userAgent,
        public ?string $jobId = null,
    ) {
    }

    public function isBatch(): bool
    {
        return $this->batchId !== null;
    }
}
