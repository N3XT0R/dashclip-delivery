<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\StatusEnum;
use App\Exceptions\Video\VideoUnavailableForOfferException;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\ChannelVideoBlock;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentRepository
{
    public function __construct(private readonly VideoRepository $videoRepository)
    {
    }

    /**
     * @param list<int> $ids
     * @return EloquentCollection<int, Assignment>
     */
    public function findByIds(array $ids): EloquentCollection
    {
        return Assignment::whereIn('id', $ids)->get();
    }

    /**
     * Query offers on the user's channels, excluding visibility through submitted clips alone.
     *
     * @return Builder<Assignment>
     */
    public function forOperator(User $user): Builder
    {
        return Assignment::query()->whereHas('channel', static function (Builder $channel) use ($user): void {
            $channel->userHasAccess($user);
        });
    }

    /**
     * Query the offers visible to the given user: assignments on a channel
     * they have access to, or assignments for videos backed by their own
     * clips.
     * @param User $user
     * @return Builder
     */
    public function visibleForUser(User $user): Builder
    {
        return Assignment::query()->whereHas('video')->where(function (Builder $query) use ($user): void {
            $query->whereHas('channel', static function (Builder $channel) use ($user): void {
                $channel->userHasAccess($user);
            })->orWhere(static function (Builder $inner) use ($user): void {
                $inner->hasUsersClips($user);
            });
        });
    }

    /**
     * Create a new assignment linking a video to a channel within a batch.
     *
     * The video row is locked first, the same lock deleting a video takes, so no offer can be
     * created for a video that is being deleted.
     * @param Video $video
     * @param Channel $channel
     * @param Batch $batch
     * @param bool $viaPreferred
     * @return Assignment
     * @throws VideoUnavailableForOfferException when the video was deleted in the meantime
     */
    public function createAssignment(
        Video $video,
        Channel $channel,
        Batch $batch,
        bool $viaPreferred = false
    ): Assignment {
        return DB::transaction(function () use ($video, $channel, $batch, $viaPreferred): Assignment {
            if ($this->videoRepository->lockForUpdate((int)$video->getKey()) === null) {
                throw new VideoUnavailableForOfferException('The video was deleted and cannot be offered.');
            }

            $expired = $this->findExpiredAssignment($video, $channel);
            if ($expired instanceof Assignment) {
                return $this->openNextRound($expired, $batch, $viaPreferred);
            }

            return Assignment::query()->create([
                'video_id' => $video->getKey(),
                'channel_id' => $channel->getKey(),
                'batch_id' => $batch->getKey(),
                'status' => StatusEnum::QUEUED->value,
                'via_preferred_channel' => $viaPreferred,
            ]);
        });
    }

    /**
     * Channels a video must not be offered to right now, per video.
     *
     * A channel is blocked while its offer is still open, was returned or picked up, and while its
     * expired offer has used up the configured rounds. As long as a video can still reach a channel
     * that never had it, channels with an expired offer stay blocked as well, so every channel gets
     * the video once before any channel gets it again.
     *
     * @param Collection<int, Video> $poolVideos
     * @param Collection<int, int> $poolChannelIds channels the videos can reach in this run
     * @param int $rounds configured number of offer rounds per channel
     * @return array<int, Collection<int, int>> video_id => blocked channel ids
     */
    public function preloadBlockedChannels(Collection $poolVideos, Collection $poolChannelIds, int $rounds): array
    {
        $reachable = $poolChannelIds->map(fn ($id): int => (int)$id)->unique();

        return Assignment::query()
            ->whereIn('video_id', $poolVideos->pluck('id'))
            ->get()
            ->groupBy('video_id')
            ->map(function (Collection $rows) use ($reachable, $rounds): Collection {
                $repeatable = $rows->filter(
                    fn (Assignment $row): bool => $row->status === StatusEnum::EXPIRED->value
                        && (int)$row->offer_round < $rounds
                );
                $blocked = $rows->reject(
                    fn (Assignment $row): bool => $repeatable->contains('id', $row->getKey())
                )->pluck('channel_id');

                $hasUntouchedChannel = $reachable->diff($rows->pluck('channel_id'))->isNotEmpty();

                return $hasUntouchedChannel
                    ? $blocked->concat($repeatable->pluck('channel_id'))->unique()->values()
                    : $blocked->unique()->values();
            })
            ->all();
    }

    private function findExpiredAssignment(Video $video, Channel $channel): ?Assignment
    {
        return Assignment::query()
            ->where('video_id', $video->getKey())
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::EXPIRED->value)
            ->first();
    }

    /**
     * Offer an expired assignment again: the row is reused, so the unique pair stays intact.
     */
    private function openNextRound(Assignment $assignment, Batch $batch, bool $viaPreferred): Assignment
    {
        $assignment->forceFill([
            'batch_id' => $batch->getKey(),
            'status' => StatusEnum::QUEUED->value,
            'offer_round' => (int)$assignment->offer_round + 1,
            'via_preferred_channel' => $viaPreferred,
            'expires_at' => null,
            'last_notified_at' => null,
            'download_token' => null,
        ])->save();

        return $assignment;
    }

    /**
     * Determine whether an assignment already links this video to this channel.
     * @param int $videoId
     * @param int $channelId
     * @return bool
     */
    public function existsForVideoAndChannel(int $videoId, int $channelId): bool
    {
        return Assignment::query()
            ->where('video_id', $videoId)
            ->where('channel_id', $channelId)
            ->exists();
    }

    /**
     * Count assignments created for each channel since a given point in time.
     *
     * @return Collection<int, int> channel_id => assignment count
     */
    public function countAssignmentsByChannelSince(CarbonInterface $since): Collection
    {
        return Assignment::query()
            ->where('created_at', '>=', $since)
            ->select('channel_id')
            ->selectRaw('COUNT(*) as assignments_count')
            ->groupBy('channel_id')
            ->pluck('assignments_count', 'channel_id')
            ->map(fn (int|string $count): int => (int)$count);
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
            ->whereHas('video')
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
        return Assignment::with('video.clips.preferredChannel')
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
        return Assignment::with('video.clips.preferredChannel')
            ->where('channel_id', $channel->getKey())
            ->whereIn('id', $ids)
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->get();
    }

    /**
     * Fetch available and previously downloaded offers within the authorized channel and batch.
     *
     * @param Collection<int, int> $ids
     * @return EloquentCollection<int, Assignment>
     */
    public function fetchDownloadableForChannel(Channel $channel, Collection $ids, ?Batch $batch = null): EloquentCollection
    {
        return Assignment::with('videoWithTrashed.clipsWithTrashed.preferredChannel')
            ->where('channel_id', $channel->getKey())
            ->when($batch, fn (Builder $query) => $query->where('batch_id', $batch->getKey()))
            ->whereIn('id', $ids)
            ->whereIn('status', StatusEnum::getReturnableStatuses())
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereHas('videoWithTrashed', static fn (Builder $video): Builder => $video->whereStoresItsFiles())
            ->get();
    }

    /**
     * Retrieve assignments that are ready for offering to a channel.
     */
    public function fetchPending(Batch $batch, Channel $channel): EloquentCollection
    {
        return Assignment::with(['video.clips'])
            ->whereHas('video')
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->orderBy('id')
            ->get();
    }

    /**
     * Mark ready assignments in a channel offer batch as notified with the shared offer expiry.
     */
    public function markReadyAsNotifiedForChannel(Batch $batch, Channel $channel, CarbonInterface $expiresAt): int
    {
        return Assignment::query()
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->update([
                'status' => StatusEnum::NOTIFIED->value,
                'expires_at' => $expiresAt,
                'last_notified_at' => now(),
            ]);
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
            ->available()
            ->where('channel_id', $channel->getKey());
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

    /**
     * Expire assignments that have passed their TTL and apply cooldown blocks.
     * @param int $cooldownDays
     * @return int
     */
    public function expireAssignments(int $cooldownDays): int
    {
        $count = 0;
        Assignment::query()->whereIn('status', StatusEnum::getReadyStatus())
            ->where('expires_at', '<', now())
            ->chunkById(500, function ($items) use (&$count, $cooldownDays) {
                foreach ($items as $assignment) {
                    $assignment->update(['status' => StatusEnum::EXPIRED->value]);
                    ChannelVideoBlock::query()->updateOrCreate(
                        ['channel_id' => $assignment->channel_id, 'video_id' => $assignment->video_id],
                        ['until' => now()->addDays($cooldownDays)]
                    );
                    $count++;
                }
            });

        return $count;
    }
}
