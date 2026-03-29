<?php

declare(strict_types=1);

namespace App\DTO\Ingest;

use DateTimeInterface;

final readonly class IngestStepStatusDto
{
    public function __construct(
        public string $name,
        public string $status,
        public DateTimeInterface $finishedAt,
        public int $attempts = 0,
        public bool $isCurrent = false,
    ) {
    }
}
