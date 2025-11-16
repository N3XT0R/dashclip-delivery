<?php

declare(strict_types=1);

namespace App\Repository;

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
}