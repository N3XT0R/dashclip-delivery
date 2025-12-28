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

    /**
     * Mark assignments as unused (rejected) for a given batch and channel.
     * @param Batch $batch
     * @param Channel $channel
     * @param Collection $ids
     * @return bool
     */
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

    /**
     * Mark an assignment as downloaded and create a download record.
     * @param Assignment $assignment
     * @param string $ip
     * @param string|null $userAgent
     * @return Download
     */
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

    /**
     * Retrieve assignments that have been picked up by a channel.
     * @param Batch $batch
     * @param Channel $channel
     * @return EloquentCollection
     */
    public function fetchPickedUp(Batch $batch, Channel $channel): EloquentCollection
    {
        return Assignment::with('video.clips')
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::PICKEDUP->value)
            ->get();
    }


    /**
     * @param Batch $batch
     * @param Channel $channel
     * @param Collection $ids
     * @return EloquentCollection<Assignment>
     * @deprecated use fetchForZipForChannel instead
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
     * Retrieve assignments for zip download for a specific channel.
     * @param Channel $channel
     * @param Collection $ids
     * @return EloquentCollection
     */
    public function fetchForZipForChannel(Channel $channel, Collection $ids): EloquentCollection
    {
        return Assignment::with('video.clips')
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

    /**
     * Get the count of available offers for a user.
     * @param User $user
     * @return int
     */
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

    /**
     * Get the count of expired offers for a user.
     * @param User $user
     * @return int
     */
    public function getExpiredOffersCountForUser(User $user): int
    {
        return Assignment::query()->hasUsersClips($user)
            ->where('status', StatusEnum::EXPIRED->value)
            ->count();
    }

    /**
     * Get the query builder for available offers for a channel.
     * @param Channel $channel
     * @return Builder
     */
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

    /**
     * Get the count of downloaded offers for a channel.
     * @param Channel|null $channel
     * @return int
     */
    public function getDownloadedOffersCountForChannel(?Channel $channel = null): int
    {
        if (null === $channel) {
            return 0;
        }
        return Assignment::query()
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::PICKEDUP->value)
            ->count();
    }

    /**
     * Get the count of expired offers for a channel.
     * @param Channel|null $channel
     * @return int
     */
    public function getExpiredOffersCountForChannel(?Channel $channel = null): int
    {
        if (null === $channel) {
            return 0;
        }
        return Assignment::query()
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::EXPIRED->value)
            ->count();
    }

    /**
     * Get the count of returned (rejected) offers for a channel.
     * @param Channel|null $channel
     * @return int
     */
    public function getReturnedOffersCountForChannel(?Channel $channel = null): int
    {
        if (null === $channel) {
            return 0;
        }
        return Assignment::query()
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::REJECTED->value)
            ->count();
    }

    /**
     * Get the count of available offers for a channel.
     * @param Channel|null $channel
     * @return int
     */
    public function getAvailableOffersCountForChannel(?Channel $channel = null): int
    {
        if (null === $channel) {
            return 0;
        }
        return $this->getAvailableOfferQueryForChannel($channel)->count();
    }

    /**
     * Get the count of picked up offers for a user.
     * @param User $user
     * @return int
     */
    public function getPickedUpOffersCountForUser(User $user): int
    {
        return Assignment::query()->hasUsersClips($user)
            ->where('status', StatusEnum::PICKEDUP->value)
            ->count();
    }
}
