<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Enum\ProcessingStatusEnum;
use App\Models\Video;
use App\Repository\VideoRepository;
use Throwable;

readonly class IngestStateService
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

    public function isStepCompleted(Video $video, string $stepName): bool
    {
        return 'completed' === data_get($video->meta, "ingest.steps.{$stepName}.status");
    }

    public function dependenciesAreCompleted(Video $video, array $dependencies): bool
    {
        foreach ($dependencies as $dependency) {
            if (!$this->isStepCompleted($video, $dependency)) {
                return false;
            }
        }

        return true;
    }

    public function markStepRunning(Video $video, string $stepName): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, 'ingest.current_step', $stepName);
        data_set($meta, "ingest.steps.{$stepName}.status", 'running');
        data_set($meta, "ingest.steps.{$stepName}.error", null);

        return $this->persistMeta($video, $meta);
    }

    public function markStepCompleted(Video $video, string $stepName): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$stepName}.status", 'completed');
        data_set($meta, "ingest.steps.{$stepName}.error", null);

        return $this->persistMeta($video, $meta);
    }

    public function markStepFailed(Video $video, string $stepName, Throwable $e): bool
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$stepName}.status", 'failed');
        data_set($meta, "ingest.steps.{$stepName}.error", [
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
