<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Support\Collection;

/**
 * Per-video state for a single distribution run: which channels are blocked for
 * a video, which channels it has already been assigned to (updated as the run
 * proceeds), and which channel its submitter asked for.
 */
class VideoAssignmentContext
{
    /**
     * @param array<int, Collection<int,int>> $blockedByVideo            video_id => blocked channel ids
     * @param array<int, Collection<int,int>> $assignedChannelsByVideo   video_id => already assigned channel ids
     * @param array<int, int>                 $preferredChannelIdByVideo video_id => preferred channel id
     */
    public function __construct(
        public readonly array $blockedByVideo,
        public array $assignedChannelsByVideo,
        public readonly array $preferredChannelIdByVideo,
    ) {
    }

    /**
     * Record that a video has been assigned to a channel during this run.
     */
    public function recordAssignment(int $videoId, int $channelId): void
    {
        $this->assignedChannelsByVideo[$videoId] =
            ($this->assignedChannelsByVideo[$videoId] ?? collect())
                ->push($channelId)
                ->unique();
    }
}
