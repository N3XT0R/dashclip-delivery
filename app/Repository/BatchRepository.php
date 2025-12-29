<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\BatchTypeEnum;
use App\Models\Batch;

class BatchRepository
{
    /**
     * Mark the given assigned batch as finished.
     * @param Batch $batch
     * @param int $assigned
     * @param int $skipped
     * @return bool
     */
    public function markAssignedBatchAsFinished(Batch $batch, int $assigned, int $skipped): bool
    {
        return $batch->update([
            'finished_at' => now(),
            'stats' => [
                'assigned' => $assigned,
                'skipped' => $skipped,
            ],
        ]);
    }

    /**
     * Get the last finished assign batch.
     * @return Batch|null
     */
    public function getLastFinishedAssignBatch(): ?Batch
    {
        return Batch::query()
            ->where('type', BatchTypeEnum::ASSIGN->value)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->first();
    }

    /**
     * Find a batch by its ID.
     * @param int $id
     * @return Batch|null
     */
    public function findById(int $id): ?Batch
    {
        return Batch::query()
            ->find($id);
    }

    /**
     * Create a new batch with the provided data.
     * @param array $data
     * @return Batch
     */
    public function create(array $data): Batch
    {
        return Batch::create($data);
    }

    /**
     * Update the given batch with the provided data.
     * @param Batch $batch
     * @param array $data
     * @return bool
     */
    public function update(Batch $batch, array $data): bool
    {
        return $batch->update($data);
    }
}
