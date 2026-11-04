<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\BatchTypeEnum;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\ValueObjects\DistributionRunResult;
use App\Models\Video;
use App\Repository\BatchRepository;
use App\ValueObjects\IngestStats;
use Illuminate\Database\Eloquent\Builder;
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
            throw new RuntimeException('No assign batch found.');
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
            throw new RuntimeException('No assign batch found.');
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

    public function finishAssignBatch(Batch $batch, DistributionRunResult $result): bool
    {
        return $this->batchRepository->markAssignedBatchAsFinished($batch, $result);
    }


    /**
     * Collect videos for the distribution pool:
     *  - unassigned videos (ever)
     *  - or newly added since the last completed assign batch
     *  - plus re-queueable ones (expired / returned / etc.)
     *
     * The re-queueable ones come in the order of how badly they have come off so far: the videos
     * that reached the fewest channels first, and among those the ones waiting the longest since
     * their offer ran out or was given back. Without that order the lowest video ids would take
     * every free place of every run, and a video that expired later would never get one.
     *
     * A video a channel still holds, that is one with an offer in status picked up, stays out of
     * the pool. Giving an offer back puts its video back in, even when that channel had already
     * downloaded it, because the channel said it does not use it. The returning channel itself is
     * blocked from receiving the video again.
     * @param Batch|null $lastFinished
     * @return Collection<Video>
     */
    public function collectPoolVideos(?Batch $lastFinished): Collection
    {
        // Unassigned EVER ODER neuer als letzter Batch
        $newOrUnassigned = Video::query()
            ->where('processing_status', ProcessingStatusEnum::Completed->value)
            ->whereDoesntHave('assignments')
            ->when($lastFinished, function ($q) use ($lastFinished) {
                $q->orWhere('created_at', '>', $lastFinished?->finished_at);
            })
            ->orderBy('id')
            ->get();

        // Requeue-Fälle (z. B. expired)
        $requeueIds = Assignment::query()
            ->whereIn('status', StatusEnum::getRequeueStatuses())
            ->whereDoesntHave('video.assignments', fn (Builder $query): Builder => $query->where(
                'status',
                StatusEnum::PICKEDUP->value
            ))
            ->groupBy('video_id')
            ->select('video_id')
            ->selectRaw('COUNT(DISTINCT channel_id) as channels_served')
            ->selectRaw(
                'MAX(CASE WHEN status = ? THEN updated_at ELSE expires_at END) as waiting_since',
                [StatusEnum::REJECTED->value]
            )
            ->orderBy('channels_served')
            ->orderBy('waiting_since')
            ->get()
            ->pluck('video_id');

        $requeueVideos = $this->loadInGivenOrder($requeueIds);

        return $newOrUnassigned->concat($requeueVideos)->unique('id');
    }

    /**
     * Load the given videos and keep the order they were handed over in.
     *
     * @param Collection<int, int> $videoIds
     * @return Collection<int, Video>
     */
    private function loadInGivenOrder(Collection $videoIds): Collection
    {
        if ($videoIds->isEmpty()) {
            return collect();
        }

        $position = $videoIds->values()->flip();

        return Video::query()
            ->whereIn('id', $videoIds)
            ->get()
            ->sortBy(fn (Video $video): int => (int)$position->get($video->getKey(), PHP_INT_MAX))
            ->values();
    }

    public function collectVideosForAssign(): Collection
    {
        $lastFinished = $this->batchRepository->getLastFinishedAssignBatch();
        return $this->collectPoolVideos($lastFinished);
    }


}
