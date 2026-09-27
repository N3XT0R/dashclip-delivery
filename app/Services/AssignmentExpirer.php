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
     * Expire assignments that have reached their TTL and apply cooldown blocks.
     *
     * @param int $cooldownDays how long the channel is blocked for that video afterwards
     * @param int $graceMinutes offers running out within this many minutes are expired as well
     * @return int
     */
    public function expire(int $cooldownDays, int $graceMinutes = 0): int
    {
        $batch = $this->batchRepository->create([
            'type' => BatchTypeEnum::ASSIGN->value,
            'started_at' => now()
        ]);

        $count = $this->assignmentRepository->expireAssignments($cooldownDays, $graceMinutes);

        $this->batchRepository->update($batch, [
            'finished_at' => now(),
            'stats' => [
                'expired' => $count
            ]
        ]);
        return $count;
    }
}
