<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Enum\Ingest\IngestResult;
use App\Services\Ingest\Contracts\IngestPipelineInterface;
use App\Services\Ingest\Contracts\IngestStepInterface;
use Closure;
use Illuminate\Support\Facades\Log;
use Traversable;

class IngestPipeline implements IngestPipelineInterface
{
    /** @var IngestStepInterface[] */
    private array $steps;

    public function __construct(iterable $steps)
    {
        $this->steps = $steps instanceof Traversable ? iterator_to_array($steps) : (array)$steps;
    }

    public function handle(IngestContext $context): IngestResult
    {
        $pipeline = array_reduce(
            array_reverse($this->steps),
            fn(Closure $next, IngestStepInterface $step) => static fn(IngestContext $ctx) => $step->handle($ctx, $next),
            fn() => IngestResult::NEW
        );

        try {
            return $pipeline($context);
        } catch (\Throwable $e) {
            Log::error('Ingest pipeline failed', ['exception' => $e]);
            return IngestResult::ERR;
        }
    }
}
