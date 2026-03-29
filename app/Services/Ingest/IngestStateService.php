<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Enum\Ingest\IngestStepEnum;
use App\Enum\ProcessingStatusEnum;
use App\Events\Ingest\VideoCompleted;
use App\Models\Video;
use App\Repository\VideoRepository;
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

        return $this->videoRepository->updateProcessingStatus(
            $video,
            $status
        );
    }

    /**
     * Checks if the given step is marked as completed in the video's meta information.
     * @param Video $video
     * @param IngestStepEnum $step
     * @return bool
     */
    public function isStepCompleted(Video $video, IngestStepEnum $step): bool
    {
        return 'completed' === data_get(
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

        VideoCompleted::dispatch($video, $this->videoRepository->getUploaderUser($video));
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
}
