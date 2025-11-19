<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ChannelPoolDto;
use App\Enum\BatchTypeEnum;
use App\Models\Batch;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Repository\ChannelVideoBlockRepository;
use App\Repository\ClipRepository;
use App\Repository\VideoRepository;
use App\ValueObjects\AssignmentRun;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Service to distribute videos across channels based on quotas, weights, and blocks.
 * @todo refactor to smaller services?
 */
readonly class AssignmentDistributor
{

    public function __construct(
        private AssignmentRepository $assignmentRepository,
        private AssignmentService $assignmentService,
        private ChannelVideoBlockRepository $channelVideoBlockRepository,
        private BatchService $batchService
    ) {
    }

    /**
     * Distribute new and requeueable videos across channels.
     *
     * @param  int|null  $quotaOverride  optional quota per channel
     * @return array{assigned:int, skipped:int}
     */
    public function distribute(?int $quotaOverride = null): array
    {
        $batchService = $this->batchService;
        $assignmentRepo = $this->assignmentRepository;
        $assignmentService = $this->assignmentService;
        $channelVideoBlockRepository = $this->channelVideoBlockRepository;


        $batch = $batchService->startBatch(BatchTypeEnum::ASSIGN);
        // 1) Kandidaten einsammeln (neu, unzugewiesen, requeue)
        $poolVideos = $this->collectPoolOrAbort($batch);

        $videoRepo = app(VideoRepository::class);
        $uploaderPools = $videoRepo->partitionByUploader($poolVideos);

        $totalAssigned = 0;
        $totalSkipped = 0;

        foreach ($uploaderPools as $uploaderId => $videosOfUploader) {
            if ($videosOfUploader->isEmpty()) {
                continue;
            }

            // 2) Bundles vollständig machen
            $poolVideos = $assignmentService->expandBundles($videosOfUploader)->values();

            // 3) Kanäle + Rotationspool + Quotas
            $channelPoolDto = $this->prepareChannelsOrAbort($quotaOverride, $batch);

            // 4) Gruppenbildung (Videos, die zu einem Bundle gehören, bleiben zusammen)
            $groups = $this->buildGroups($videosOfUploader);

            // 5) Preloads zur Minimierung von N+1
            $blockedByVideo = $channelVideoBlockRepository->preloadActiveBlocks($videosOfUploader);
            $assignedChannelsByVideo = $assignmentRepo->preloadAssignedChannels($videosOfUploader);

            // 6) ValueObject
            $run = new AssignmentRun(
                groups: $groups,
                channelPool: $channelPoolDto,
                blockedByVideo: $blockedByVideo,
                assignedChannelsByVideo: $assignedChannelsByVideo,
                batch: $batch,
                uploaderId: $uploaderId
            );

            // 7) Verteilung
            [$assigned, $skipped] = $this->assignGroups($run);

            $totalAssigned += $assigned;
            $totalSkipped += $skipped;
        }

        $batchService->finishAssignBatch($batch, $totalAssigned, $totalSkipped);

        return ['assigned' => $totalAssigned, 'skipped' => $totalSkipped];
    }


    public function calculateBlockedChannels(Collection $group, $blockedByVideo): array
    {
        return $group
            ->flatMap(fn(Video $video) => $blockedByVideo[$video->getKey()] ?? collect())
            ->unique()
            ->all();
    }

    public function collectPoolOrAbort(Batch $batch): Collection
    {
        $poolVideos = $this->batchService->collectVideosForAssign();

        if ($poolVideos->isEmpty()) {
            $this->batchService->finishAssignBatch($batch, 0, 0);
            throw new RuntimeException('Nichts zu verteilen.');
        }

        return $poolVideos;
    }

    public function prepareChannelsOrAbort(?int $quotaOverride, Batch $batch): ChannelPoolDto
    {
        $channelService = app(ChannelService::class);
        $channelPoolDto = $channelService->prepareChannelsAndPool($quotaOverride);

        if ($channelPoolDto->channels->isEmpty()) {
            $this->batchService->finishAssignBatch($batch, 0, 0);
            throw new RuntimeException('Keine Kanäle konfiguriert.');
        }

        return $channelPoolDto;
    }

    public function assignGroups(AssignmentRun $run): array
    {
        $assigned = 0;
        $skipped = 0;

        $channelService = app(ChannelService::class);
        $assignmentService = app(AssignmentService::class);

        foreach ($run->groups as $group) {
            $blockedChannelIds = $this->calculateBlockedChannels($group, $run->blockedByVideo);

            $channel = $channelService->pickTargetChannel(
                $group,
                $run->channelPool->rotationPool,
                $run->channelPool->quota,
                $blockedChannelIds,
                $run->assignedChannelsByVideo
            );

            if (!$channel) {
                $skipped += $group->count();
                continue;
            }

            $assigned += $assignmentService->assignGroupToChannel(
                $group,
                $channel,
                $run
            );

            if ($run->quotasUsedUp()) {
                break;
            }
        }

        return [$assigned, $skipped];
    }


    /**
     * @param  Collection<Video>  $poolVideos
     * @return Collection
     */
    public function buildGroups(Collection $poolVideos): Collection
    {
        $clipRepository = app(ClipRepository::class);
        $videoRepository = app(VideoRepository::class);
        $groups = collect();

        $bundleMap = $clipRepository->getBundleVideoMap($poolVideos->pluck('id'));

        $handled = [];
        foreach ($poolVideos as $video) {
            if (array_key_exists($video->getKey(), $handled)) {
                continue;
            }

            $bundleIds = $bundleMap->first(fn(Collection $ids) => $ids->contains($video->getKey()));

            if ($bundleIds) {
                $group = $videoRepository->getVideosByIdsFromPool($poolVideos, $bundleIds);
                foreach ($bundleIds as $id) {
                    $handled[$id] = true;
                }
            } else {
                $group = collect([$video]);
                $handled[$video->getKey()] = true;
            }

            $groups->push($group);
        }

        return $groups;
    }

}
