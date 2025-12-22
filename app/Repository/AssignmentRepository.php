<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

    public function markUnused(Batch $batch, Channel $channel, Collection $ids): bool
    {
        return Assignment::query()
                ->where('batch_id', $batch->getKey())
                ->where('channel_id', $channel->getKey())
                ->whereIn('id', $ids)
                ->where('status', StatusEnum::PICKEDUP->value)
                ->update([
                    'status' => StatusEnum::REJECTED->value,
                    'download_token' => null,
                    'expires_at' => null,
                    'last_notified_at' => null,
                ]) > 0;
    }

    public function markDownloaded(Assignment $assignment, string $ip, ?string $userAgent): Download
    {
        $assignment->update(['status' => StatusEnum::PICKEDUP->value]);

        return Download::query()->create([
            'assignment_id' => $assignment->getKey(),
            'downloaded_at' => now(),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'bytes_sent' => $assignment->getAttribute('video')?->getAttribute('bytes'),
        ]);
    }

    public function fetchPickedUp(Batch $batch, Channel $channel): EloquentCollection
    {
        return Assignment::with('video.clips')
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::PICKEDUP->value)
            ->get();
    }


    /**
     * @param  Batch  $batch
     * @param  Channel  $channel
     * @param  Collection  $ids
     * @return EloquentCollection<Assignment>
     */
    public function fetchForZip(Batch $batch, Channel $channel, Collection $ids): EloquentCollection
    {
        return Assignment::with('video.clips')
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->whereIn('id', $ids)
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->get();
    }

    /**
     * Retrieve assignments that are ready for offering to a channel.
     */
    public function fetchPending(Batch $batch, Channel $channel): EloquentCollection
    {
        return Assignment::with(['video.clips'])
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->orderBy('id')
            ->get();
    }


    public function getAvailableOffersCountForUser(User $user): int
    {
        return Assignment::query()->hasUsersClips($user)
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    public function getExpiredOffersCountForUser(User $user): int
    {
        return Assignment::query()->hasUsersClips($user)
            ->where('status', StatusEnum::EXPIRED->value)
            ->count();
    }

    private function getAvailableOfferQueryForChannel(Channel $channel): Builder
    {
        return Assignment::query()
            ->where('channel_id', $channel->getKey())
            ->whereIn('status', [StatusEnum::QUEUED->value, StatusEnum::NOTIFIED->value])
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function getAvailableOffersCountForChannel(Channel $channel): int
    {
        return $this->getAvailableOfferQueryForChannel($channel)->count();
    }

    public function getPickedUpOffersCountForUser(User $user): int
    {
        return Assignment::query()->hasUsersClips($user)
            ->where('status', StatusEnum::PICKEDUP->value)
            ->count();
    }
}