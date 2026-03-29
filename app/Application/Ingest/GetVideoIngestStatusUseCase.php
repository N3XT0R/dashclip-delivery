<?php

declare(strict_types=1);

namespace App\Application\Ingest;

use App\DTO\Ingest\IngestStatusDto;
use App\DTO\Ingest\IngestStepStatusDto;
use App\Models\Video;
use App\Pipelines\Ingest\Step\IngestStepInterface;
use App\Repository\VideoRepository;
use Illuminate\Contracts\Container\Container;

use function count;
use function is_array;
use function round;

final readonly class GetVideoIngestStatusUseCase
{
    public function __construct(
        private VideoRepository $videoRepository,
        private Container $container,
    ) {
    }

    public function handle(int|Video $video): ?IngestStatusDto
    {
        if ($video instanceof Video) {
            $video = $video->getKey();
        } else {
            $video = $this->videoRepository->findById($video);
        }

        if (null === $video) {
            return null;
        }

        $pipelineSteps = $this->getPipelineSteps();
        $ingestData = $this->extractIngestData($video->processing_result ?? []);

        $storedSteps = $this->extractStoredSteps($ingestData);
        $currentStep = $this->extractCurrentStep($ingestData);

        [$steps, $completedSteps] = $this->buildStepDtos(
            $pipelineSteps,
            $storedSteps,
            $currentStep
        );

        return $this->buildStatusDto(
            $steps,
            $completedSteps,
            $currentStep
        );
    }

    /**
     * @return iterable<IngestStepInterface>
     */
    private function getPipelineSteps(): iterable
    {
        return $this->container->tagged('ingest.step');
    }

    /**
     * @param array<string, mixed> $processingResult
     * @return array<string, mixed>
     */
    private function extractIngestData(array $processingResult): array
    {
        return is_array($processingResult['ingest'] ?? null)
            ? $processingResult['ingest']
            : [];
    }

    /**
     * @param array<string, mixed> $ingestData
     * @return array<string, mixed>
     */
    private function extractStoredSteps(array $ingestData): array
    {
        return is_array($ingestData['steps'] ?? null)
            ? $ingestData['steps']
            : [];
    }

    private function extractCurrentStep(array $ingestData): ?string
    {
        return isset($ingestData['current_step']) && is_string($ingestData['current_step'])
            ? $ingestData['current_step']
            : null;
    }

    /**
     * @param iterable<IngestStepInterface> $pipelineSteps
     * @param array<string, mixed> $storedSteps
     * @return array{0: list<IngestStepStatusDto>, 1: int}
     */
    private function buildStepDtos(
        iterable $pipelineSteps,
        array $storedSteps,
        ?string $currentStep
    ): array {
        $steps = [];
        $completedSteps = 0;

        foreach ($pipelineSteps as $step) {
            $stepName = $step->name()->value;

            $stepData = is_array($storedSteps[$stepName] ?? null)
                ? $storedSteps[$stepName]
                : [];

            $status = $this->extractStatus($stepData);
            $attempts = $this->extractAttempts($stepData);

            if ($status === 'completed') {
                ++$completedSteps;
            }

            $steps[] = new IngestStepStatusDto(
                name: $stepName,
                status: $status,
                attempts: $attempts,
                isCurrent: $currentStep === $stepName,
            );
        }

        return [$steps, $completedSteps];
    }

    /**
     * @param array<string, mixed> $stepData
     */
    private function extractStatus(array $stepData): string
    {
        return isset($stepData['status']) && is_string($stepData['status'])
            ? $stepData['status']
            : 'pending';
    }

    /**
     * @param array<string, mixed> $stepData
     */
    private function extractAttempts(array $stepData): int
    {
        return isset($stepData['attempts']) && is_int($stepData['attempts'])
            ? $stepData['attempts']
            : 0;
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
