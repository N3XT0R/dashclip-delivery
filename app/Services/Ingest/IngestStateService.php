<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Models\Video;
use App\Repository\VideoRepository;
use Throwable;

readonly class IngestStateService
{
    public function __construct(
        private VideoRepository $videoRepository,
    ) {
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

    public function markWorkflowRunning(Video $video): void
    {
        $meta = $video->meta ?? [];

        data_set($meta, 'ingest.status', 'running');
        data_set($meta, 'ingest.last_error', null);

        $this->persistMeta($video, $meta);
    }

    public function markWorkflowCompleted(Video $video): void
    {
        $meta = $video->meta ?? [];

        data_set($meta, 'ingest.status', 'completed');
        data_set($meta, 'ingest.current_step', null);
        data_set($meta, 'ingest.last_error', null);
        data_set($meta, 'ingest.finished_at', now()?->toIso8601String());

        $this->persistMeta($video, $meta);
    }

    public function markWorkflowFailed(Video $video, string $stepName, Throwable $e): void
    {
        $meta = $video->meta ?? [];

        data_set($meta, 'ingest.status', 'failed');
        data_set($meta, 'ingest.current_step', $stepName);
        data_set($meta, 'ingest.last_error', [
            'message' => $e->getMessage(),
            'type' => $e::class,
            'at' => now()?->toIso8601String(),
        ]);

        $this->persistMeta($video, $meta);
    }

    public function markStepRunning(Video $video, string $stepName): void
    {
        $meta = $video->meta ?? [];

        data_set($meta, 'ingest.current_step', $stepName);
        data_set($meta, "ingest.steps.{$stepName}.status", 'running');
        data_set($meta, "ingest.steps.{$stepName}.error", null);
        data_set($meta, "ingest.steps.{$stepName}.started_at", now()?->toIso8601String());

        $attempts = (int)data_get($meta, "ingest.steps.{$stepName}.attempts", 0);
        data_set($meta, "ingest.steps.{$stepName}.attempts", $attempts + 1);

        $this->persistMeta($video, $meta);
    }

    public function markStepCompleted(Video $video, string $stepName): void
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$stepName}.status", 'completed');
        data_set($meta, "ingest.steps.{$stepName}.finished_at", now()?->toIso8601String());
        data_set($meta, "ingest.steps.{$stepName}.error", null);

        $this->persistMeta($video, $meta);
    }

    public function markStepFailed(Video $video, string $stepName, Throwable $e): void
    {
        $meta = $video->meta ?? [];

        data_set($meta, "ingest.steps.{$stepName}.status", 'failed');
        data_set($meta, "ingest.steps.{$stepName}.finished_at", now()?->toIso8601String());
        data_set($meta, "ingest.steps.{$stepName}.error", [
            'message' => $e->getMessage(),
            'type' => $e::class,
            'at' => now()?->toIso8601String(),
        ]);

        $this->persistMeta($video, $meta);
    }

    private function persistMeta(Video $video, array $meta): void
    {
        $this->videoRepository->update($video, [
            'meta' => $meta,
        ]);

        $video->meta = $meta;
    }
}
