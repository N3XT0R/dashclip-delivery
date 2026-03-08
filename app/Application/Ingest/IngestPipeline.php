<?php

declare(strict_types=1);

namespace App\Application\Ingest;

use App\Application\Ingest\Step\IngestStepInterface;

final readonly class IngestPipeline
{
    /**
     * @param iterable<IngestStepInterface> $steps
     */
    public function __construct(
        private iterable $steps
    ) {
    }

    public function handle(IngestContext $context): IngestContext
    {
        foreach ($this->steps as $step) {
            $context = $step->handle($context);

            if ($context->isDuplicate) {
                break;
            }
        }

        return $context;
    }
}
