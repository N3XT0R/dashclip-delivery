<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Clip;
use Illuminate\Support\Collection;

class ClipRepository
{
    public function getBundleKeysByVideoIds(iterable $videoIds): Collection
    {
        return Clip::query()
            ->whereIn('video_id', $videoIds)
            ->whereNotNull('bundle_key')
            ->pluck('bundle_key')
            ->unique();
    }

    public function getBundleVideoIdsByBundleKeys(iterable $bundleKeys): Collection
    {
        return Clip::query()
            ->whereIn('bundle_key', $bundleKeys)
            ->pluck('video_id')
            ->unique();
    }

    /**
     * @return Collection<Clip>
     */
    public function getClipsWhereUserIdIsNull(): Collection
    {
        return Clip::query()
            ->whereNull('user_id')
            ->whereNotNull('submitted_by')
            ->where('submitted_by', '!=', '')
            ->get();
    }


    public function getBundleKeysForVideos(iterable $videoIds): Collection
    {
        return Clip::query()
            ->whereIn('video_id', $videoIds)
            ->whereNotNull('bundle_key')
            ->pluck('bundle_key')
            ->unique()
            ->values();
    }

    /**
     * @param  iterable  $bundleKeys
     * @return Collection
     */
    public function getVideoIdsForBundleKeys(iterable $bundleKeys): Collection
    {
        return Clip::query()
            ->whereIn('bundle_key', $bundleKeys)
            ->pluck('video_id')
            ->unique()
            ->values();
    }

    /**
     * @param  Collection  $poolVideos
     * @return array
     */
    public function getBundleVideoMap(Collection $poolVideos): array
    {
        return Clip::query()
            ->whereIn('video_id', $poolVideos->pluck('id'))
            ->whereNotNull('bundle_key')
            ->get()
            ->groupBy('bundle_key')
            ->map(
                fn(Collection $group) => $group->pluck('video_id')->unique()->values()
            )
            ->all();
    }
}