<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\BatchTypeEnum;
use App\Models\Batch;
use App\ValueObjects\IngestStats;
use RuntimeException;

class BatchService
{
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

    public function finalizeStats(Batch $batch, IngestStats $stats): void
    {
        $batch->update([
            'finished_at' => now(),
            'stats' => $stats->toArray(),
        ]);
    }
}