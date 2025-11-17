<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\StatusEnum;
use App\Models\Clip;
use App\Models\Video;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VideoRepository
{
    public function filterDeletableVideoIds(Collection $candidateIds, Carbon $threshold): Collection
    {
        if ($candidateIds->isEmpty()) {
            return collect();
        }

        return Video::query()
            ->whereIn('id', $candidateIds)
            ->whereDoesntHave('assignments', function ($q) use ($threshold) {
                $q->where('status', '!=', StatusEnum::PICKEDUP->value)
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>=', $threshold)
                    ->orWhereDoesntHave('downloads');
            })
            ->pluck('id');
    }

    public function deleteVideosByIds(Collection $videoIds): int
    {
        if ($videoIds->isEmpty()) {
            return 0;
        }

        return Video::query()
            ->whereIn('id', $videoIds)
            ->delete();
    }

    public function fetchOriginalNames(Collection $videoIds): Collection
    {
        if ($videoIds->isEmpty()) {
            return collect();
        }

        return Video::query()
            ->whereIn('id', $videoIds)
            ->pluck('original_name')
            ->filter();
    }

    public function firstOrCreate(array $data): Video
    {
        return Video::query()->firstOrCreate($data);
    }


    public function getVideosByIds(iterable $ids): Collection
    {
        return Video::query()
            ->whereIn('id', $ids)
            ->get();
    }

    public function isDuplicate(string $hash): bool
    {
        return Video::query()->where('hash', $hash)->exists();
    }

    public function getClipForVideo(Video $video, int $startSec, int $endSec): ?Clip
    {
        return $video->clips()->where('start_sec', $startSec)->where('end_sec', $endSec)->first();
    }

    /**
     * @param  Collection<Video>  $pool
     * @param  iterable  $ids
     * @return Collection
     */
    public function getVideosByIdsFromPool(Collection $pool, iterable $ids): Collection
    {
        $idLookup = collect($ids)
            ->map(fn($id) => (int)$id)   // <--- WICHTIG!
            ->flip();                    // O(1) lookup

        return $pool
            ->filter(fn(Video $video) => $idLookup->has($video->getKey()))
            ->values();
    }
}
