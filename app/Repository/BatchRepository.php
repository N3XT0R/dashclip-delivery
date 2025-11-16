<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\BatchTypeEnum;
use App\Models\Batch;

class BatchRepository
{
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

    public function getLastFinishedAssignBatch(): ?Batch
    {
        return Batch::query()
            ->where('type', BatchTypeEnum::ASSIGN->value)
            ->whereNotNull('finished_at')
            ->orderByDesc('finished_at')
            ->first();
    }
}