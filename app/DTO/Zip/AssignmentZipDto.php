<?php

declare(strict_types=1);

namespace App\DTO\Zip;

readonly class AssignmentZipDto
{
    public function __construct(
        public ?int $batchId,
        public int $channelId,
        public array $assignmentIds,
        public string $ip,
        public ?string $userAgent,
    ) {
    }

    public function isBatch(): bool
    {
        return $this->batchId !== null;
    }
}
