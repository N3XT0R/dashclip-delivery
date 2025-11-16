<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Assignment;
use App\Models\ChannelVideoBlock;
use App\Models\Clip;
use Illuminate\Support\Collection;

class AssignmentRepository
{
    public function create(array $data): Assignment
    {
        return Assignment::query()->create($data);
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
        $handled = [];
        $groups = collect();

        $bundleMap = Clip::query()
            ->whereIn('video_id', $poolVideos->pluck('id'))
            ->whereNotNull('bundle_key')
            ->get()
            ->groupBy('bundle_key')
            ->map(fn(Collection $g) => $g->pluck('video_id')->unique());

        foreach ($poolVideos as $video) {
            if (in_array($video->getKey(), $handled, true)) {
                continue;
            }

            $bundleIds = $bundleMap->first(fn(Collection $ids) => $ids->contains($video->id));

            if ($bundleIds) {
                $group = $poolVideos->whereIn('id', $bundleIds)->values();
                $handled = array_merge($handled, $bundleIds->all());
            } else {
                $group = collect([$video]);
                $handled[] = $video->getKey();
            }

            $groups->push($group);
        }

        return $groups;
    }
}