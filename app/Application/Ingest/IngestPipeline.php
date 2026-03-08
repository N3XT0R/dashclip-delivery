<?php

declare(strict_types=1);

namespace App\Application\Ingest;

use App\Application\Ingest\Context\IngestContext;
use App\Application\Ingest\Step\IngestStepInterface;
use App\Services\Ingest\IngestStateService;
use Throwable;

/**
 * The IngestPipeline class orchestrates the execution of a series of ingest steps for a given video.
 * It manages the state of each step and the overall workflow using the IngestStateService.
 */
final readonly class IngestPipeline
{
    /**
     * @param iterable<IngestStepInterface> $steps
     */
    public function __construct(
        private iterable $steps,
        private IngestStateService $ingestStateService,
    ) {
    }

    /**
     * Executes the ingest pipeline for the given context.
     * @param IngestContext $context
     * @return IngestContext
     * @throws Throwable
     */
    public function handle(IngestContext $context): IngestContext
    {
        $this->ingestStateService->markWorkflowRunning($context->video);

        foreach ($this->steps as $step) {
            if ($this->ingestStateService->isStepCompleted($context->video, $step->name())) {
                continue;
            }

            if (!$this->ingestStateService->dependenciesAreCompleted($context->video, $step->dependsOn())) {
                continue;
            }

            if (!$step->isApplicable($context)) {
                continue;
            }

            $this->ingestStateService->markStepRunning($context->video, $step->name());

            try {
                $context = $step->handle($context);
                $this->ingestStateService->markStepCompleted($context->video, $step->name());
            } catch (Throwable $e) {
                $this->ingestStateService->markStepFailed($context->video, $step->name(), $e);
                $this->ingestStateService->markWorkflowFailed($context->video, $step->name(), $e);

                throw $e;
            }

            if ($context->isDuplicate) {
                break;
            }
        }

        $this->ingestStateService->markWorkflowCompleted($context->video);

        return $context;
    }
}
