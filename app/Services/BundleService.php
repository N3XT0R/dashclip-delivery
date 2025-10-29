<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Video;
use App\Repository\ClipRepository;
use Illuminate\Support\Collection;

class BundleService
{

    public function __construct(private ClipRepository $clipRepository)
    {
    }

    public function expand(Collection $poolVideos): Collection
    {
        $videoIds = $poolVideos->pluck('id');
        $bundleKeys = $this->clipRepository->getBundleKeysByVideoIds($videoIds);

        if ($bundleKeys->isEmpty()) {
            return $poolVideos;
        }

        $bundleVideoIds = $this->clipRepository->getBundleVideoIdsByBundleKeys($bundleKeys);

        if ($bundleVideoIds->isEmpty()) {
            return $poolVideos;
        }

        $bundleVideos = Video::query()->whereIn('id', $bundleVideoIds)->get();

        return $poolVideos->concat($bundleVideos)->unique('id');
    }
}