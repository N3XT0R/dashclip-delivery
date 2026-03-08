<?php

declare(strict_types=1);

namespace App\Application\Ingest;

use App\Application\Ingest\Context\IngestContext;
use App\Application\Ingest\Step\IngestStepInterface;
use App\Enum\ProcessingStatusEnum;
use App\Services\Ingest\IngestStateService;
use Throwable;

/**
 * Orchestrates the execution of ingest steps for a video.
 * It manages the processing status and step states using the IngestStateService.
 * The pipeline ensures that steps are executed in the correct order based on their dependencies and applicability.
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
        $this->ingestStateService->markProcessingStatus(
            $context->video,
            ProcessingStatusEnum::Running
        );

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
