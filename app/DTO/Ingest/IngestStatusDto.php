<?php

declare(strict_types=1);

namespace App\DTO\Ingest;

final readonly class IngestStatusDto
{
    /**
     * @param list<IngestStepStatusDto> $steps
     */
    public function __construct(
        public array $steps,
        public int $totalSteps,
        public int $completedSteps,
        public int $progressPercent,
        public ?string $currentStep,
    ) {
    }
}
