<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use App\Repository\ChannelRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PreferredChannelService
{
    public function __construct(private readonly ChannelRepository $channelRepository)
    {
    }

    /**
     * Resolve a raw preferred_channel value from info.csv to a channel id.
     *
     * Rules:
     *  - '' / whitespace only     → null (no preference)
     *  - digits only              → lookup by channel id (no name fallback)
     *  - otherwise                → lookup by name (case-insensitive, trimmed)
     * The channel must exist AND must not be paused
     * (is_video_reception_paused === false), otherwise null is returned.
     *
     * @param string $raw
     * @return int|null resolved channel id, or null when not resolvable
     */
    public function resolveRawValue(string $raw): ?int
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $channel = ctype_digit($value)
            ? $this->channelRepository->findById((int) $value)
            : $this->channelRepository->findByNameInsensitive($value);

        if (!$channel instanceof Channel || $channel->is_video_reception_paused) {
            return null;
        }

        return (int) $channel->getKey();
    }

    /**
     * Build a video_id => preferred channel_id map for a set of videos.
     *
     * Only videos whose clips carry exactly one distinct non-null
     * preferred_channel_id are included. Videos with conflicting values are
     * omitted and logged.
     *
     * @param  Collection<int,Video>  $videos
     * @return array<int,int>
     */
    public function preloadForVideos(Collection $videos): array
    {
        $videoIds = $videos->map(fn (Video $v) => (int) $v->getKey())->all();
        if ($videoIds === []) {
            return [];
        }

        $map = [];

        Clip::query()
            ->whereIn('video_id', $videoIds)
            ->whereNotNull('preferred_channel_id')
            ->get(['video_id', 'preferred_channel_id'])
            ->groupBy('video_id')
            ->each(function (Collection $clips, int|string $videoId) use (&$map): void {
                $distinct = $clips->pluck('preferred_channel_id')->map(fn ($id) => (int) $id)->unique()->values();

                if ($distinct->count() === 1) {
                    $map[(int) $videoId] = $distinct->first();
                    return;
                }

                Log::warning('conflicting preferred_channel for video {video}', [
                    'video' => (int) $videoId,
                    'channels' => $distinct->all(),
                ]);
            });

        return $map;
    }

    /**
     * Determine the effective preferred channel for a (bundle) group.
     *
     *  - no video in the group has a preference        → null
     *  - all present preferences are identical         → that channel_id
     *  - the group contains differing preferences      → null (logged)
     *
     * @param  Collection<int,Video>  $groupVideos
     * @param  array<int,int>  $preferredChannelIdByVideo
     * @return int|null
     */
    public function resolveForGroup(Collection $groupVideos, array $preferredChannelIdByVideo): ?int
    {
        $preferences = $groupVideos
            ->map(fn (Video $v) => $preferredChannelIdByVideo[(int) $v->getKey()] ?? null)
            ->filter(fn (?int $id) => $id !== null)
            ->unique()
            ->values();

        if ($preferences->isEmpty()) {
            return null;
        }

        if ($preferences->count() === 1) {
            return (int) $preferences->first();
        }

        Log::warning('conflicting preferred_channel within group {videos}', [
            'videos' => $groupVideos->map(fn (Video $v) => (int) $v->getKey())->all(),
            'channels' => $preferences->all(),
        ]);

        return null;
    }
}
