<?php

declare(strict_types=1);

namespace App\Application\Ingest;

use App\Application\Ingest\Context\IngestContext;
use App\Application\Ingest\Step\IngestStepInterface;
use InvalidArgumentException;

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
            if ($step instanceof IngestStepInterface === false) {
                throw new InvalidArgumentException(
                    sprintf(
                        'All steps must implement %s, but got %s',
                        IngestStepInterface::class,
                        get_class($step)
                    )
                );
            }
            $context = $step->handle($context);

            if ($context->isDuplicate) {
                break;
            }
        }

        return $context;
    }
}
