<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\DTO\ChannelPoolDto;
use App\Models\Batch;
use Illuminate\Support\Collection;

class AssignmentRun
{
    public function __construct(
        public readonly Collection $groups,
        public readonly ChannelPoolDto $channelPool,
        public readonly array $blockedByVideo,
        public array $assignedChannelsByVideo,
        public readonly Batch $batch,
        public readonly string $uploaderType,
        public readonly string|int $uploaderId,
    ) {
    }

    public function recordAssignment(int $videoId, int $channelId): void
    {
        $this->assignedChannelsByVideo[$videoId] =
            ($this->assignedChannelsByVideo[$videoId] ?? collect())
                ->push($channelId)
                ->unique();
    }

    public function decrementQuota(int $channelId): void
    {
        $this->channelPool->quota[$channelId]--;
    }

    public function quotasUsedUp(): bool
    {
        return collect($this->channelPool->quota)
            ->every(fn(int $q) => $q <= 0);
    }
}
