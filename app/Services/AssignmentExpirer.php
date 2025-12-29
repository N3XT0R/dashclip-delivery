<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\BatchTypeEnum;
use App\Enum\StatusEnum;
use App\Models\{Assignment, Batch, ChannelVideoBlock};

class AssignmentExpirer
{
    /**
     * Expire assignments that have passed their TTL and apply cooldown blocks.
     */
    public function expire(int $cooldownDays): int
    {
        $batch = Batch::query()->create(['type' => BatchTypeEnum::ASSIGN->value, 'started_at' => now()]);
        $count = 0;

        Assignment::query()->where('status', StatusEnum::NOTIFIED->value)
            ->where('expires_at', '<', now())
            ->whereNot('status', StatusEnum::PICKEDUP->value)
            ->chunkById(500, function ($items) use (&$count, $cooldownDays) {
                foreach ($items as $assignment) {
                    $assignment->update(['status' => StatusEnum::EXPIRED->value]);
                    ChannelVideoBlock::query()->updateOrCreate(
                        ['channel_id' => $assignment->channel_id, 'video_id' => $assignment->video_id],
                        ['until' => now()->addDays($cooldownDays)]
                    );
                    $count++;
                }
            });

        $batch->update(['finished_at' => now(), 'stats' => ['expired' => $count]]);
        return $count;
    }
}
