<?php

declare(strict_types=1);

namespace App\Application\Ingest;

use App\Application\Ingest\Context\IngestContext;
use App\Application\Ingest\Step\IngestStepInterface;
use App\Enum\ProcessingStatusEnum;
use App\Services\Ingest\IngestStateService;
use Throwable;

/**
 * Orchestrates the ingest processing pipeline for a video.
 *
 * The pipeline executes a sequence of ingest steps that implement
 * {@see IngestStepInterface}. Each step is executed only if:
 *
 * - the step has not already been completed
 * - all declared dependencies are completed
 * - the step is applicable to the current {@see IngestContext}
 *
 * Step states and the overall processing status are managed through
 * the {@see IngestStateService}, allowing failed pipelines to be
 * safely retried without repeating completed steps.
 *
 * Because step completion state is persisted, the pipeline is
 * retryable and can safely resume after interruptions, executing
 * only the steps that have not yet been completed.
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
     * Returns the list of ingest steps in the pipeline.
     * @return iterable<IngestStepInterface>
     */
    public function getSteps(): iterable
    {
        return $this->steps;
    }

    /**
     * Executes the ingest pipeline for the given context.
     * @param IngestContext $context
     * @return IngestContext
     * @throws Throwable
     */
    public function handle(IngestContext $context): IngestContext
    {
        $this->ingestStateService->markProcessingStatus(
            $context->video,
            ProcessingStatusEnum::Running
        );

        /**
         * @var IngestStepInterface $step
         */
        foreach ($this->getSteps() as $step) {
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
                $this->ingestStateService->markProcessingStatus(
                    $context->video,
                    ProcessingStatusEnum::Failed
                );

                throw $e;
            }

            if ($context->isDuplicate) {
                break;
            }
        }

        $this->ingestStateService->markProcessingStatus(
            $context->video,
            ProcessingStatusEnum::Completed
        );

        return $context;
    }
}
