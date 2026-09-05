<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ChannelPoolDto;
use App\DTO\UploaderPoolInfo;
use App\Enum\BatchTypeEnum;
use App\Models\Batch;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Repository\ChannelVideoBlockRepository;
use App\Repository\ClipRepository;
use App\Repository\VideoRepository;
use App\ValueObjects\AssignmentRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
     * @param int|null $quotaOverride optional quota per channel
     * @return array{assigned:int, skipped:int, deferred:int}
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
        $uploaderPools = $videoRepo->partitionByTeamOrUploader($poolVideos);

        $totalAssigned = 0;
        $totalSkipped = 0;
        $totalDeferred = 0;

        /** @var UploaderPoolInfo $uploaderPool */
        foreach ($uploaderPools as $uploaderPool) {
            if ($uploaderPool->videos->isEmpty()) {
                continue;
            }

            // 2) Bundles vollständig machen
            /**
             * @var Collection<Video> $videosOfUploader
             */
            $videosOfUploader = $assignmentService->expandBundles($uploaderPool->videos)->values();

            $uploaderType = $uploaderPool->type;
            $uploaderId = $uploaderPool->id;

            // 3) Kanäle + Rotationspool + Quotas
            try {
                $channelPoolDto = $this->prepareChannelsOrAbort(
                    $quotaOverride,
                    $batch,
                    $uploaderType,
                    $uploaderId
                );

                // 4) Gruppenbildung (Videos, die zu einem Bundle gehören, bleiben zusammen)
                $groups = $this->buildGroups($videosOfUploader);

                // 5) Preloads zur Minimierung von N+1
                $blockedByVideo = $channelVideoBlockRepository->preloadActiveBlocks($videosOfUploader);
                $assignedChannelsByVideo = $assignmentRepo->preloadAssignedChannels($videosOfUploader);
                $preferredChannelIdByVideo = app(PreferredChannelService::class)
                    ->preloadForVideos($videosOfUploader);

                // 6) ValueObject
                $run = new AssignmentRun(
                    groups: $groups,
                    channelPool: $channelPoolDto,
                    blockedByVideo: $blockedByVideo,
                    assignedChannelsByVideo: $assignedChannelsByVideo,
                    preferredChannelIdByVideo: $preferredChannelIdByVideo,
                    batch: $batch,
                    uploaderType: $uploaderType,
                    uploaderId: $uploaderId
                );

                // 7) Verteilung
                [$assigned, $skipped, $deferred] = $this->assignGroups($run);
                $totalAssigned += $assigned;
                $totalSkipped += $skipped;
                $totalDeferred += $deferred;
            } catch (\Throwable $e) {
                Log::warning(
                    'Error during distribution for uploader {type}#{id}: {message}',
                    [
                        'type' => $uploaderType,
                        'id' => $uploaderId,
                        'message' => $e->getMessage(),
                    ]
                );
            }
        }

        $batchService->finishAssignBatch($batch, $totalAssigned, $totalSkipped, $totalDeferred);

        return ['assigned' => $totalAssigned, 'skipped' => $totalSkipped, 'deferred' => $totalDeferred];
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
            throw new RuntimeException('nothing to assign');
        }

        return $poolVideos;
    }

    public function prepareChannelsOrAbort(
        ?int $quotaOverride,
        Batch $batch,
        string $uploaderType,
        string|int $uploaderId
    ): ChannelPoolDto {
        $channelService = app(ChannelService::class);
        $channelPoolDto = $channelService->prepareChannelsAndPool(
            $quotaOverride,
            $uploaderType,
            $uploaderId
        );

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
        $deferred = 0;

        $channelService = app(ChannelService::class);
        $assignmentService = app(AssignmentService::class);
        $preferredChannelService = app(PreferredChannelService::class);

        foreach ($run->groups as $group) {
            $blockedChannelIds = $this->calculateBlockedChannels($group, $run->blockedByVideo);

            $preferredChannelId = $preferredChannelService->resolveForGroup($group, $run->preferredChannelIdByVideo);

            if ($preferredChannelId !== null) {
                $preferredOutcome = $this->assignToPreferredChannel(
                    $group,
                    $preferredChannelId,
                    $blockedChannelIds,
                    $run,
                    $channelService,
                    $assignmentService
                );

                if ($preferredOutcome === 'assigned') {
                    $assigned += $group->count();
                    if ($run->quotasUsedUp()) {
                        break;
                    }
                    continue;
                }

                if ($preferredOutcome === 'deferred') {
                    $deferred += $group->count();
                    continue;
                }
                // 'fallthrough' → normal round-robin below
            }

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

            $assigned += $assignmentService->assignGroupToChannel($group, $channel, $run);

            if ($run->quotasUsedUp()) {
                break;
            }
        }

        return [$assigned, $skipped, $deferred];
    }

    /**
     * Try to place a group on its wished channel.
     *
     * @return 'assigned'|'deferred'|'fallthrough'
     *   - 'assigned'    the group was assigned to the wished channel
     *   - 'deferred'    the wished channel is valid but cannot take the group
     *                   right now (quota/block) — retry next run
     *   - 'fallthrough' the wished channel is not usable at all (not in pool)
     *                   or the wish is already fulfilled — use the algorithm
     */
    private function assignToPreferredChannel(
        Collection $group,
        int $preferredChannelId,
        array $blockedChannelIds,
        AssignmentRun $run,
        ChannelService $channelService,
        AssignmentService $assignmentService
    ): string {
        $channel = $channelService->findPooledChannel($run->channelPool->rotationPool, $preferredChannelId);

        if ($channel === null) {
            return 'fallthrough';
        }

        // Wish already fulfilled for every video in the group → let the
        // algorithm handle any remaining distribution instead of deferring
        // forever.
        $allAlreadyOnPreferred = $group->every(function ($video) use ($preferredChannelId, $run) {
            $assigned = $run->assignedChannelsByVideo[$video->getKey()] ?? collect();
            return $assigned->contains($preferredChannelId);
        });
        if ($allAlreadyOnPreferred) {
            return 'fallthrough';
        }

        $accepts = $channelService->channelAcceptsGroup(
            $channel,
            $group,
            $run->channelPool->quota,
            $blockedChannelIds,
            $run->assignedChannelsByVideo
        );

        if (!$accepts) {
            return 'deferred';
        }

        $assignmentService->assignGroupToChannel($group, $channel, $run, viaPreferred: true);

        return 'assigned';
    }


    /**
     * @param Collection<Video> $poolVideos
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
