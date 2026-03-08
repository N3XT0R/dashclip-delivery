<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Enum\Ingest\IngestStepEnum;
use App\Enum\ProcessingStatusEnum;
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

    public function markProcessingStatus(Video $video, ProcessingStatusEnum $status): bool
    {
        $video->processing_status = $status;

        return $this->videoRepository->update($video, [
            'processing_status' => $status->value,
        ]);
    }

    public function isStepCompleted(Video $video, IngestStepEnum $step): bool
    {
        return 'completed' === data_get(
                $video->meta,
                "ingest.steps.{$step->value}.status"
            );
    }

    /**
     * @param list<IngestStepEnum> $dependencies
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

    public function markStepCompleted(Video $video, IngestStepEnum $step): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$step->value}.status", 'completed');
        data_set($meta, "ingest.steps.{$step->value}.error", null);

        return $this->persistMeta($video, $meta);
    }

    public function markStepFailed(Video $video, IngestStepEnum $step, Throwable $e): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$step->value}.status", 'failed');
        data_set($meta, "ingest.steps.{$step->value}.error", [
            'message' => $e->getMessage(),
            'type' => $e::class,
        ]);

        return $this->persistMeta($video, $meta);
    }

    private function persistMeta(Video $video, array $meta): bool
    {
        $video->meta = $meta;

        return $this->videoRepository->update($video, [
            'meta' => $meta,
        ]);
    }
}
