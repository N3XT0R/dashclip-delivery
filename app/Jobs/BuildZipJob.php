<?php

namespace App\Jobs;

use App\DTO\Zip\AssignmentZipDto;
use App\Enum\DownloadStatusEnum;
use App\Services\DownloadCacheService;
use Throwable;
use App\Exceptions\Zip\ZipBuildException;
use App\Repository\AssignmentRepository;
use App\Repository\BatchRepository;
use App\Repository\ChannelRepository;
use App\Services\{AssignmentService, Zip\ZipService};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to build a ZIP file for a batch of assignments in a specific channel.
 */
class BuildZipJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;
    use Dispatchable;
    use InteractsWithQueue;

    /** Max attempts before the job is marked as failed */
    public int $tries = 1;

    public int $timeout = 1200;       // 20 minutes for big ZIPs

    public bool $failOnTimeout = true;

    /**
     * Create a new job instance.
     * @param AssignmentZipDto $assignmentZipDto
     * @param Authenticatable|null $user
     */
    public function __construct(
        protected AssignmentZipDto $assignmentZipDto,
        protected ?Authenticatable $user = null,
    ) {
    }

    public function getAssignmentIds(): array
    {
        return $this->assignmentZipDto->assignmentIds;
    }

    /**
     * Execute the job.
     * @param AssignmentService $assignments
     * @param ZipService $svc
     * @return void
     */
    public function handle(AssignmentService $assignments, ZipService $svc): void
    {
        $jobId = $this->assignmentZipDto->jobId;
        $batch = $this->assignmentZipDto->isBatch()
            ? app(BatchRepository::class)->findById($this->assignmentZipDto->batchId)
            : null;
        $channel = app(ChannelRepository::class)->findById($this->assignmentZipDto->channelId);

        if (!$channel) {
            throw new ZipBuildException(
                sprintf(
                    'Channel with ID %s not found',
                    $this->assignmentZipDto->channelId
                )
            );
        }

        $assignmentIds = collect($this->assignmentZipDto->assignmentIds);

        if ($jobId !== null) {
            $items = app(AssignmentRepository::class)->fetchDownloadableForChannel($channel, $assignmentIds, $batch);
            if ($items->count() !== $assignmentIds->count()) {
                throw new ZipBuildException('Some selected offers are no longer available.');
            }
        } elseif ($batch) {
            $items = $assignments->fetchForZip($batch, $channel, $assignmentIds);
        } else {
            $items = app(AssignmentRepository::class)->fetchForZipForChannel(
                $channel,
                $assignmentIds
            );

            $jobId = 'channel_' . $this->assignmentZipDto->channelId . '_' . hash(
                'sha256',
                implode('_', $this->assignmentZipDto->assignmentIds)
            );
        }


        $svc->build(
            $batch,
            $channel,
            $items,
            $this->assignmentZipDto->ip,
            $this->assignmentZipDto->userAgent ?? '',
            $jobId
        );

        activity()
            ->causedBy($this->user)
            ->performedOn($channel)
            ->withProperties([
                'attributes' => [
                    'channel_id' => $this->assignmentZipDto->channelId,
                    'channel_name' => $channel->name,
                    'batch_id' => $this->assignmentZipDto->batchId,
                    'assignments' => count($this->assignmentZipDto->assignmentIds),
                ],
            ])
            ->log('ZIP-File created');
    }

    /** Publish terminal failures for clients polling a newly prepared download. */
    public function failed(?Throwable $exception): void
    {
        if ($this->assignmentZipDto->jobId !== null) {
            app(DownloadCacheService::class)->setStatus($this->assignmentZipDto->jobId, DownloadStatusEnum::FAILED->value);
        }
    }
}
