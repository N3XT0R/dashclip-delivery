<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\ChannelVideoBlock;
use Illuminate\Support\Collection;

class ChannelVideoBlockRepository
{
    /**
     * Load all active blocks (with an “until” timestamp in the future) for the entire pool in advance.
     *
     * @return array<int, Collection<int,int>> video_id => collection(channel_id)
     */
    public function preloadActiveBlocks(Collection $poolVideos): array
    {
        return ChannelVideoBlock::query()
            ->whereIn('video_id', $poolVideos->pluck('id'))
            ->where('until', '>', now())
            ->get()
            ->groupBy('video_id')
            ->map(fn(Collection $rows) => $rows->pluck('channel_id')->unique())
            ->all();
    }
}