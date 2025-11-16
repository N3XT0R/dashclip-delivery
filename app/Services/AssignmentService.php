<?php

namespace App\Services;

use App\Enum\StatusEnum;
use App\Models\{Assignment, Batch, Channel, Video};
use App\Repository\AssignmentRepository;
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
        return Assignment::with(['video.clips'])
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->orderBy('id')
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

    public function fetchPickedUp(Batch $batch, Channel $channel): EloquentCollection
    {
        return Assignment::with('video')
            ->where('batch_id', $batch->getKey())
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::PICKEDUP->value)
            ->get();
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
     * @param  Collection<Video>  $group
     * @param  Channel  $channel
     * @param  AssignmentRun  $run
     * @return array
     */
    public function assignGroupToChannel(Collection $group, Channel $channel, AssignmentRun $run): array
    {
        foreach ($group as $video) {
            $this->assignmentRepository->createAssignment($video, $channel, $run->batch);

            $run->recordAssignment($video->getKey(), $channel->getKey());
            $run->decrementQuota($channel->getKey());
        }

        return [$run->assignedChannelsByVideo];
    }
}

