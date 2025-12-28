<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\UploaderPoolInfo;
use App\Enum\StatusEnum;
use App\Models\Clip;
use App\Models\Team;
use App\Models\User;
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
     * @param Collection<Video> $pool
     * @param iterable $ids
     * @return Collection
     */
    public function getVideosByIdsFromPool(Collection $pool, iterable $ids): Collection
    {
        $idLookup = collect($ids)
            ->map(fn($id) => (int)$id)  // cast
            ->flip();                   // lookup map: id => index

        return $pool
            ->filter(fn(Video $video) => $idLookup->has($video->getKey()))
            ->values();
    }

    /**
     * Partitions videos by uploader (Clip → user_id).
     * Videos without uploader are grouped under key "0".
     *
     * @param Collection<Video> $videos
     * @return array<int, Collection<Video>>
     */
    public function partitionByUploader(Collection $videos): array
    {
        return $videos
            ->groupBy(fn(Video $video) => $video->clips()->first()?->user_id ?? 0) // 0 = "unknown uploader"
            ->all();
    }

    /**
     * Partitions videos by team slug, or by uploader (Clip → user_id) if no team is assigned.
     * Videos without team and uploader are grouped under key "user:0".
     *
     * @param Collection<Video> $videos
     * @return array<int, UploaderPoolInfo>
     */
    public function partitionByTeamOrUploader(Collection $videos): array
    {
        /** @var Collection<string, Collection<Video>> $grouped */
        $grouped = $videos
            ->groupBy(function (Video $video) {
                $video->loadMissing(['team', 'clips']);
                if ($video->team && $video->team->slug) {
                    return 'team:' . $video->team->slug;
                }

                // Fallback: Uploader (Clip → user_id)
                $uploaderId = $video->clips->first()?->user_id;
                if ($uploaderId) {
                    return 'user:' . $uploaderId;
                }
                return 'user:0';
            });

        return $grouped
            ->map(function (Collection $videosOfUploader, string $key) {
                [$type, $id] = explode(':', $key, 2);
                $id = $type === 'user' && is_numeric($id) ? (int)$id : $id;

                return new UploaderPoolInfo($type, $id, $videosOfUploader);
            })
            ->values()
            ->all();
    }


    public function getVideoCountForUser(User $user): int
    {
        return Video::query()->hasUsersClips($user)->count();
    }

    public function getVideosCountForTeam(Team $team): int
    {
        return Video::query()->where('team_id', $team->getKey())->count();
    }

    public function getVideosWithoutTeam(): Collection
    {
        return Video::query()->whereNull('team_id')->get();
    }

    public function update(Video $video, array $data): bool
    {
        return $video->update($data);
    }
}
