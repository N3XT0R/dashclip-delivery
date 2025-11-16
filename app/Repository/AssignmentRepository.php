<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\ChannelVideoBlock;
use App\Models\Clip;
use App\Models\Video;
use Illuminate\Support\Collection;

class AssignmentRepository
{
    public function createAssignment(Video $video, Channel $channel, Batch $batch): Assignment
    {
        return Assignment::query()->create([
            'video_id' => $video->getKey(),
            'channel_id' => $channel->getKey(),
            'batch_id' => $batch->getKey(),
            'status' => StatusEnum::QUEUED->value,
        ]);
    }


    /**
     * Lade alle aktiven Blocks (bis "until" in der Zukunft) für den gesamten Pool vor.
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

    /**
     * Lade alle bereits (irgendwann) zugewiesenen Kanäle je Video vor,
     * damit wir nicht doppelt an denselben Kanal verteilen.
     *
     * @return array<int, Collection<int,int>> video_id => collection(channel_id)
     */
    public function preloadAssignedChannels(Collection $poolVideos): array
    {
        return Assignment::query()
            ->whereIn('video_id', $poolVideos->pluck('id'))
            ->get()
            ->groupBy('video_id')
            ->map(fn(Collection $rows) => $rows->pluck('channel_id')->unique())
            ->all();
    }


    public function buildGroups(Collection $poolVideos): Collection
    {
        $groups = collect();

        $bundleMap = Clip::query()
            ->whereIn('video_id', $poolVideos->pluck('id'))
            ->whereNotNull('bundle_key')
            ->get()
            ->groupBy('bundle_key')
            ->map(fn(Collection $g) => $g->pluck('video_id')->unique());

        $handled = [];
        foreach ($poolVideos as $video) {
            if (array_key_exists($video->getKey(), $handled)) {
                continue;
            }

            $bundleIds = $bundleMap->first(fn(Collection $ids) => $ids->contains($video->id));

            if ($bundleIds) {
                $group = $poolVideos->whereIn('id', $bundleIds)->values();
                foreach ($bundleIds as $id) {
                    $handled[$id] = true;
                }
            } else {
                $group = collect([$video]);
                $handled[$video->getKey()] = true;
            }

            $groups->push($group);
        }

        return $groups;
    }

    /**
     * Ensure that all videos belonging to a bundle are included whenever one of them is present in the pool.
     * @param  Collection<Video>  $poolVideos
     * @return Collection<Video>
     */
    public function expandBundles(Collection $poolVideos): Collection
    {
        $videoIds = $poolVideos->pluck('id');

        $bundleKeys = Clip::query()
            ->whereIn('video_id', $videoIds)
            ->whereNotNull('bundle_key')
            ->pluck('bundle_key')
            ->unique();

        if ($bundleKeys->isEmpty()) {
            return $poolVideos;
        }

        $bundleVideoIds = Clip::query()
            ->whereIn('bundle_key', $bundleKeys)
            ->pluck('video_id')
            ->unique();

        if ($bundleVideoIds->isEmpty()) {
            return $poolVideos;
        }

        $bundleVideos = Video::query()->whereIn('id', $bundleVideoIds)->get();

        return $poolVideos->concat($bundleVideos)->unique('id');
    }
}