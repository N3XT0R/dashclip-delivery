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
        public readonly string|int $uploaderId,
        public array $assignedByUploader = [],
        public array $skippedByBlock = []
    ) {
        $this->channelQuota =& $this->channelPool->channelQuota;
        $this->uploaderQuota =& $this->channelPool->uploaderQuotaMatrix;
    }

    /** @var array<int,int> */
    public array $channelQuota;

    /** @var array<int,array<int|string,int>> */
    public array $uploaderQuota;

    public function recordAssignment(int $videoId, int $channelId): void
    {
        $this->assignedChannelsByVideo[$videoId] =
            ($this->assignedChannelsByVideo[$videoId] ?? collect())
                ->push($channelId)
                ->unique();
    }

    public function decrementChannelQuota(int $channelId): void
    {
        if (array_key_exists($channelId, $this->channelQuota)) {
            $this->channelQuota[$channelId]--;
        }
    }

    public function decrementUploaderQuota(int $channelId, int|string $uploaderId): void
    {
        if (isset($this->uploaderQuota[$channelId][$uploaderId])) {
            $this->uploaderQuota[$channelId][$uploaderId]--;
        }
    }

    public function uploaderQuotaUsedUpForChannel(int $channelId, int|string $uploaderId): bool
    {
        return ($this->uploaderQuota[$channelId][$uploaderId] ?? 0) <= 0;
    }

    public function recordAssignmentStats(int $channelId, int|string $uploaderId, int $count): void
    {
        $this->assignedByUploader[$channelId][$uploaderId] =
            ($this->assignedByUploader[$channelId][$uploaderId] ?? 0) + $count;
    }

    public function recordSkippedByBlock(array $blockedChannelIds, int $count): void
    {
        foreach ($blockedChannelIds as $channelId) {
            $this->skippedByBlock[$channelId][$this->uploaderId] =
                ($this->skippedByBlock[$channelId][$this->uploaderId] ?? 0) + $count;
        }
    }

    public function quotasUsedUp(): bool
    {
        foreach ($this->channelQuota as $channelId => $quota) {
            if ($quota <= 0) {
                continue;
            }

            $remainingUploaderQuota = $this->uploaderQuota[$channelId][$this->uploaderId] ?? 0;
            if ($remainingUploaderQuota > 0) {
                return false;
            }
        }

        return true;
    }
}
