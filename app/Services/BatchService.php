<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\BatchTypeEnum;
use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Video;
use App\Repository\BatchRepository;
use App\ValueObjects\IngestStats;
use Illuminate\Support\Collection;
use RuntimeException;

class BatchService
{
    public function __construct(private BatchRepository $batchRepository)
    {
    }


    public function getLatestAssignBatch(): Batch
    {
        $assignBatch = Batch::query()
            ->where('type', BatchTypeEnum::ASSIGN->value)
            ->whereNotNull('finished_at')
            ->latest('id')
            ->first();
        if (false === $assignBatch instanceof Batch) {
            throw new RuntimeException('Kein Assign-Batch gefunden.');
        }

        return $assignBatch;
    }

    public function getAssignBatchById(int $id): Batch
    {
        $assignBatch = Batch::query()
            ->where('type', BatchTypeEnum::ASSIGN->value)
            ->whereNotNull('finished_at')
            ->whereKey($id)
            ->first();

        if (false === $assignBatch instanceof Batch) {
            throw new RuntimeException('Kein Assign-Batch gefunden.');
        }

        return $assignBatch;
    }

    public function createNewBatch(BatchTypeEnum $type): Batch
    {
        $batch = new Batch();
        $batch->type = $type->value;
        $batch->started_at = now();
        $batch->save();

        return $batch;
    }

    public function updateStats(Batch $batch, IngestStats $stats): bool
    {
        return $batch->update([
            'stats' => $stats->toArray(),
        ]);
    }

    public function finalizeStats(Batch $batch, IngestStats $stats): bool
    {
        return $batch->update([
            'finished_at' => now(),
            'stats' => $stats->toArray(),
        ]);
    }

    public function startBatch(BatchTypeEnum $batchTypeEnum): Batch
    {
        return Batch::query()->create([
            'type' => $batchTypeEnum->value,
            'started_at' => now(),
        ]);
    }

    public function getLastFinishedAssignBatch(): ?Batch
    {
        return $this->batchRepository->getLastFinishedAssignBatch();
    }

    public function finishAssignBatch(Batch $batch, int $assigned, int $skipped): bool
    {
        return $this->batchRepository->markAssignedBatchAsFinished($batch, $assigned, $skipped);
    }


    /**
     * Collect videos for the distribution pool:
     *  - unassigned videos (ever)
     *  - or newly added since the last completed assign batch
     *  - plus re-queueable ones (expired / returned / etc.)
     * @param  Batch|null  $lastFinished
     * @return Collection<Video>
     */
    public function collectPoolVideos(?Batch $lastFinished): Collection
    {
        // Unassigned EVER ODER neuer als letzter Batch
        $newOrUnassigned = Video::query()
            ->whereDoesntHave('assignments')
            ->when($lastFinished, function ($q) use ($lastFinished) {
                $q->orWhere('created_at', '>', $lastFinished?->finished_at);
            })
            ->orderBy('id')
            ->get();

        // Requeue-Fälle (z. B. expired)
        $requeueIds = Assignment::query()
            ->whereIn('status', StatusEnum::getRequeueStatuses())
            ->pluck('video_id')
            ->unique();

        $requeueVideos = $requeueIds->isNotEmpty()
            ? Video::query()->whereIn('id', $requeueIds)->get()
            : collect();

        return $newOrUnassigned->concat($requeueVideos)->unique('id');
    }


    public function startAssignBatch()
    {
        return $this->batchRepository->startBatch();
    }

    public function collectVideosForAssign(): Collection
    {
        $lastFinished = $this->batchRepository->getLastFinishedAssignBatch();
        return $this->collectPoolVideos($lastFinished);
    }


}