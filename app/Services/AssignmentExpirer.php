<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\BatchTypeEnum;
use App\Repository\AssignmentRepository;
use App\Repository\BatchRepository;

class AssignmentExpirer
{
    public function __construct(
        private BatchRepository $batchRepository,
        private AssignmentRepository $assignmentRepository
    ) {
    }


    /**
     * Expire assignments that have passed their TTL and apply cooldown blocks.
     */
    public function expire(int $cooldownDays): int
    {
        $batch = $this->batchRepository->create([
            'type' => BatchTypeEnum::ASSIGN->value,
            'started_at' => now()
        ]);

        $count = $this->assignmentRepository->expireAssignments($cooldownDays);

        $this->batchRepository->update($batch, [
            'finished_at' => now(),
            'stats' => [
                'expired' => $count
            ]
        ]);
        return $count;
    }
}
