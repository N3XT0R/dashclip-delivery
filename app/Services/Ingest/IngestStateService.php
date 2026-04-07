<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Enum\Ingest\IngestStepEnum;
use App\Enum\ProcessingStatusEnum;
use App\Events\Ingest\VideoCompleted;
use App\Events\Ingest\VideoFailed;
use App\Models\Video;
use App\Repository\VideoRepository;
use Carbon\Carbon;
use Throwable;

/**
 * Service responsible for managing the state of the ingest process,
 * including tracking the status of each step and handling errors.
 */
final readonly class IngestStateService
{
    public function __construct(
        private VideoRepository $videoRepository,
    ) {
    }

    /**
     * Marks the overall processing status of the video.
     * @param Video $video
     * @param ProcessingStatusEnum $status
     * @return bool
     */
    public function markProcessingStatus(Video $video, ProcessingStatusEnum $status): bool
    {
        $video->processing_status = $status;

        $result = $this->videoRepository->updateProcessingStatus(
            $video,
            $status
        );

        $uploader = $this->videoRepository->getUploaderUser($video);

        switch ($status) {
            case ProcessingStatusEnum::Completed:
                VideoCompleted::dispatch($video, $uploader);
                break;
            case ProcessingStatusEnum::Failed:
                VideoFailed::dispatch($video, $uploader);
                break;
        }

        return $result;
    }

    /**
     * Checks if the given step is marked as completed in the video's meta information.
     * @param Video $video
     * @param IngestStepEnum $step
     * @return bool
     */
    public function isStepCompleted(Video $video, IngestStepEnum $step): bool
    {
        return ProcessingStatusEnum::Completed->value === data_get(
                $video->meta,
                "ingest.steps.{$step->value}.status"
            );
    }

    /**
     * Checks if all dependencies for a given step are completed.
     * @param Video $video
     * @param list<IngestStepEnum> $dependencies
     * @return bool
     */
    public function dependenciesAreCompleted(Video $video, array $dependencies): bool
    {
        foreach ($dependencies as $dependency) {
            if (!$this->isStepCompleted($video, $dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Marks the given step as running, increments the attempt count,
     * and clears any previous error information in the video's meta.
     * @param Video $video
     * @param IngestStepEnum $step
     * @return bool
     */
    public function markStepRunning(Video $video, IngestStepEnum $step): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, 'ingest.current_step', $step->value);
        data_set($meta, "ingest.steps.{$step->value}.status", 'running');
        data_set($meta, "ingest.steps.{$step->value}.error", null);

        $attempts = (int)data_get($meta, "ingest.steps.{$step->value}.attempts", 0);
        data_set($meta, "ingest.steps.{$step->value}.attempts", $attempts + 1);

        return $this->persistMeta($video, $meta);
    }

    /**
     * Marks the given step as completed and clears any error information in the video's meta.
     * @param Video $video
     * @param IngestStepEnum $step
     * @return bool
     */
    public function markStepCompleted(Video $video, IngestStepEnum $step): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$step->value}.status", 'completed');
        data_set($meta, "ingest.steps.{$step->value}.error", null);
        data_set($meta, "ingest.steps.{$step->value}.finished_at", now()->toDateTimeString());

        return $this->persistMeta($video, $meta);
    }

    /**
     * Marks the given step as failed and records the error information in the video's meta.
     * @param Video $video
     * @param IngestStepEnum $step
     * @param Throwable $e
     * @return bool
     */
    public function markStepFailed(Video $video, IngestStepEnum $step, Throwable $e): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$step->value}.status", 'failed');
        data_set($meta, "ingest.steps.{$step->value}.finished_at", now()->toDateTimeString());
        data_set($meta, "ingest.steps.{$step->value}.error", [
            'message' => $e->getMessage(),
            'type' => $e::class,
        ]);

        return $this->persistMeta($video, $meta);
    }

    /**
     * Persists the updated meta information for the video.
     * @param Video $video
     * @param array $meta
     * @return bool
     */
    private function persistMeta(Video $video, array $meta): bool
    {
        $video->meta = $meta;

        return $this->videoRepository->update($video, [
            'meta' => $meta,
        ]);
    }

    /**
     * Retrieves the ingest-related meta information for the video, returning an empty array if not set.
     * @param Video $video
     * @return array<string, mixed>
     */
    public function getIngestData(Video $video): array
    {
        $meta = $video->meta ?? [];

        return is_array($meta['ingest'] ?? null)
            ? $meta['ingest']
            : [];
    }

    /**
     * Extracts the stored steps information from the ingest data, returning an empty array if not set.
     * @param Video $video
     * @return array<string, mixed>
     */
    public function getStoredSteps(Video $video): array
    {
        $ingestData = $this->getIngestData($video);

        return is_array($ingestData['steps'] ?? null)
            ? $ingestData['steps']
            : [];
    }

    /**
     * Extracts the current step information from the ingest data, returning null if not set or not a string.
     * @param Video $video
     * @return string|null
     */
    public function getCurrentStep(Video $video): ?string
    {
        $ingestData = $this->getIngestData($video);

        return isset($ingestData['current_step']) && is_string($ingestData['current_step'])
            ? $ingestData['current_step']
            : null;
    }

    /**
     * Extracts the status of the given step from the video's meta,
     * returning it as a string if available and valid, or 'pending' otherwise.
     * @param Video $video
     * @param IngestStepEnum $step
     * @return string
     */
    public function getStepStatus(Video $video, IngestStepEnum $step): string
    {
        $storedSteps = $this->getStoredSteps($video);
        $stepData = is_array($storedSteps[$step->value] ?? null)
            ? $storedSteps[$step->value]
            : [];

        return isset($stepData['status']) && is_string($stepData['status'])
            ? $stepData['status']
            : 'pending';
    }

    /**
     * Extracts the number of attempts for the given step from the video's meta,
     * returning it as an integer if available and valid, or 0 otherwise.
     * @param Video $video
     * @param IngestStepEnum $step
     * @return int
     */
    public function getStepAttempts(Video $video, IngestStepEnum $step): int
    {
        $storedSteps = $this->getStoredSteps($video);
        $stepData = is_array($storedSteps[$step->value] ?? null)
            ? $storedSteps[$step->value]
            : [];

        return isset($stepData['attempts']) && is_int($stepData['attempts'])
            ? $stepData['attempts']
            : 0;
    }

    /**
     * Extracts the finished_at timestamp for the given step from the video's meta,
     * returning it as a Carbon instance if available and valid, or null otherwise.
     * @param Video $video
     * @param IngestStepEnum $step
     * @return Carbon|null
     */
    public function getStepFinishedAt(Video $video, IngestStepEnum $step): ?Carbon
    {
        $storedSteps = $this->getStoredSteps($video);
        $stepData = is_array($storedSteps[$step->value] ?? null)
            ? $storedSteps[$step->value]
            : [];

        return isset($stepData['finished_at']) && is_string($stepData['finished_at'])
            ? Carbon::parse($stepData['finished_at'])
            : null;
    }

    /**
     * Counts the number of completed steps for the given video based on the provided list of steps.
     * @param list<IngestStepEnum> $steps
     */
    public function countCompletedSteps(Video $video, array $steps): int
    {
        $completedSteps = 0;

        foreach ($steps as $step) {
            if ($this->isStepCompleted($video, $step)) {
                ++$completedSteps;
            }
        }

        return $completedSteps;
    }

    /**
     * Calculates the overall progress percentage of the ingest process based on the completed steps.
     * The percentage is calculated as (completed steps / total steps) * 100 and rounded to the nearest integer.
     * If there are no steps, it returns 0% to avoid division by zero.
     * @param Video $video
     * @param list<IngestStepEnum> $steps
     * @return int
     */
    public function getProgressPercent(Video $video, array $steps): int
    {
        $totalSteps = count($steps);

        if (0 === $totalSteps) {
            return 0;
        }

        $completedSteps = $this->countCompletedSteps($video, $steps);

        return (int)round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Checks if there are any missing steps (not completed) for the given video based on the provided list of steps.
     * @param Video $video
     * @param list<IngestStepEnum> $steps
     * @return bool
     */
    public function hasMissingSteps(Video $video, array $steps): bool
    {
        foreach ($steps as $step) {
            if (!$this->isStepCompleted($video, $step)) {
                return true;
            }
        }

        return false;
    }

}
