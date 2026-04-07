<?php

declare(strict_types=1);

namespace App\Application\Ingest;

use App\DTO\Ingest\IngestStatusDto;
use App\DTO\Ingest\IngestStepStatusDto;
use App\Models\Video;
use App\Pipelines\Ingest\Step\IngestStepInterface;
use App\Repository\VideoRepository;
use App\Services\Ingest\IngestStateService;
use Illuminate\Contracts\Container\Container;

use function count;
use function round;

final readonly class GetVideoIngestStatusUseCase
{
    public function __construct(
        private VideoRepository $videoRepository,
        private Container $container,
        private IngestStateService $ingestStateService,
    ) {
    }

    public function handle(int|Video $video): ?IngestStatusDto
    {
        $video = $this->resolveVideo($video);

        if (null === $video) {
            return null;
        }

        $pipelineSteps = array_values(iterator_to_array($this->getPipelineSteps()));
        $currentStep = $this->ingestStateService->getCurrentStep($video);

        [$steps, $completedSteps] = $this->buildStepDtos(
            $video,
            $pipelineSteps,
            $currentStep
        );

        return $this->buildStatusDto(
            $steps,
            $completedSteps,
            $currentStep
        );
    }

    private function resolveVideo(int|Video $video): ?Video
    {
        if ($video instanceof Video) {
            return $video;
        }

        return $this->videoRepository->findById($video);
    }

    /**
     * @return iterable<IngestStepInterface>
     */
    private function getPipelineSteps(): iterable
    {
        return $this->container->tagged('ingest.step');
    }

    /**
     * @param Video $video
     * @param iterable<IngestStepInterface> $pipelineSteps
     * @param string|null $currentStep
     * @return array{0: list<IngestStepStatusDto>, 1: int}
     */
    private function buildStepDtos(
        Video $video,
        iterable $pipelineSteps,
        ?string $currentStep
    ): array {
        $steps = [];
        $completedSteps = 0;

        foreach ($pipelineSteps as $step) {
            $stepEnum = $step->name();
            $stepName = $stepEnum->value;

            $status = $this->ingestStateService->getStepStatus($video, $stepEnum);
            $attempts = $this->ingestStateService->getStepAttempts($video, $stepEnum);
            $finishedAt = $this->ingestStateService->getStepFinishedAt($video, $stepEnum);

            if ($this->ingestStateService->isStepCompleted($video, $stepEnum)) {
                ++$completedSteps;
            }

            $steps[] = new IngestStepStatusDto(
                name: $stepName,
                status: $status,
                finishedAt: $finishedAt,
                attempts: $attempts,
                isCurrent: $currentStep === $stepName,
            );
        }

        return [$steps, $completedSteps];
    }

    /**
     * @param list<IngestStepStatusDto> $steps
     */
    private function buildStatusDto(
        array $steps,
        int $completedSteps,
        ?string $currentStep
    ): IngestStatusDto {
        $totalSteps = count($steps);

        $progressPercent = $totalSteps > 0
            ? (int)round(($completedSteps / $totalSteps) * 100)
            : 0;

        return new IngestStatusDto(
            steps: $steps,
            totalSteps: $totalSteps,
            completedSteps: $completedSteps,
            progressPercent: $progressPercent,
            currentStep: $currentStep,
        );
    }
}
