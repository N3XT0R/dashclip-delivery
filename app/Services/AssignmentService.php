<?php

namespace App\Services;

use App\Enum\StatusEnum;
use App\Models\{Assignment, Batch, Channel, User, Video};
use App\Repository\AssignmentRepository;
use App\Repository\ClipRepository;
use App\Repository\VideoRepository;
use App\ValueObjects\AssignmentRun;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

readonly class AssignmentService
{

    public function __construct(private AssignmentRepository $assignmentRepository)
    {
    }

    /**
     * Retrieve assignments that are ready for offering to a channel.
     */
    public function fetchPending(Batch $batch, Channel $channel): EloquentCollection
    {
        return $this->assignmentRepository->fetchPending($batch, $channel);
    }

    /**
     * @param Batch $batch
     * @param Channel $channel
     * @param Collection $ids
     * @return EloquentCollection<Assignment>
     */
    public function fetchForZip(Batch $batch, Channel $channel, Collection $ids): EloquentCollection
    {
        return $this->assignmentRepository->fetchForZip($batch, $channel, $ids);
    }

    public function fetchPickedUp(Batch $batch, Channel $channel): EloquentCollection
    {
        return $this->assignmentRepository->fetchPickedUp($batch, $channel);
    }

    public function markUnused(Batch $batch, Channel $channel, Collection $ids): bool
    {
        return $this->assignmentRepository->markUnused($batch, $channel, $ids);
    }

    public function markDownloaded(Assignment $assignment, string $ip, ?string $userAgent): bool
    {
        return null !== $this->assignmentRepository->markDownloaded($assignment, $ip, $userAgent);
    }

    /**
     * Prepare an assignment for download and return a temporary URL.
     */
    public function prepareDownload(Assignment $assignment, ?int $ttlDays = null, bool $skipTracking = false): string
    {
        $plain = Str::random(40);
        $expiry = $assignment->getAttribute('expires_at');

        if (false === $skipTracking && $assignment->status === StatusEnum::QUEUED->value) {
            $assignment->status = StatusEnum::NOTIFIED->value;
            $assignment->last_notified_at = now();
        }

        if (false === $skipTracking) {
            $assignment->download_token = hash('sha256', $plain);
            if (null === $expiry) {
                $assignment->setExpiresAt($ttlDays);
                $expiry = $assignment->getAttribute('expires_at');
            }
        }

        $assignment->save();

        return URL::temporarySignedRoute(
            'assignments.download',
            $expiry,
            ['assignment' => $assignment->getKey(), 't' => $plain]
        );
    }

    /**
     * Assign group To Channel Assignment
     * @param Collection<Video> $group
     * @param Channel $channel
     * @param AssignmentRun $run
     * @return int
     */
    public function assignGroupToChannel(Collection $group, Channel $channel, AssignmentRun $run): int
    {
        $count = 0;
        foreach ($group as $video) {
            $this->assignmentRepository->createAssignment($video, $channel, $run->batch);

            $run->recordAssignment($video->getKey(), $channel->getKey());
            $run->decrementQuota($channel->getKey());
            $count++;
        }

        return $count;
    }

    /**
     * Ensure that all videos belonging to a bundle are included whenever one of them is present in the pool.
     * @param Collection<Video> $poolVideos
     * @return Collection<Video>
     */
    public function expandBundles(Collection $poolVideos): Collection
    {
        $clipRepository = app(ClipRepository::class);
        $videoIds = $poolVideos->pluck('id');

        $bundleKeys = $clipRepository->getBundleKeysForVideos($videoIds);
        if ($bundleKeys->isEmpty()) {
            return $poolVideos;
        }

        $bundleVideoIds = $clipRepository->getVideoIdsForBundleKeys($bundleKeys);

        if ($bundleVideoIds->isEmpty()) {
            return $poolVideos;
        }

        $bundleVideos = app(VideoRepository::class)->getVideosByIds($bundleVideoIds);

        return $poolVideos->concat($bundleVideos)->unique('id');
    }

    /**
     * Determine if an assignment can be returned.
     * @param Assignment|null $assignment
     * @return bool
     */
    public function canReturnAssignment(?Assignment $assignment): bool
    {
        if (null === $assignment) {
            return false;
        }

        if ($assignment->expires_at->isPast()) {
            return false;
        }

        return in_array($assignment->status, StatusEnum::getReturnableStatuses(), true);
    }

    /**
     * Return an assignment.
     * @param Assignment $assignment
     * @param User|null $user
     * @return bool
     */
    public function returnAssignment(Assignment $assignment, ?User $user = null): bool
    {
        if (false === $this->canReturnAssignment($assignment)) {
            return false;
        }

        $assignment->status = StatusEnum::REJECTED->value;

        $result = $assignment->save();
        if ($result && null !== $user) {
            activity('assignments')
                ->causedBy($user)
                ->performedOn($assignment)
                ->withProperties(['assignment_id' => $assignment->getKey(), 'channel_id' => $assignment->channel_id])
                ->log('Assignment rejected by channel');
        }

        return $result;
    }
}

