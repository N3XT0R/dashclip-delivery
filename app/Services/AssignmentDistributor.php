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
use App\ValueObjects\VideoAssignmentContext;
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
                $videoContext = new VideoAssignmentContext(
                    blockedByVideo: $channelVideoBlockRepository->preloadActiveBlocks($videosOfUploader),
                    assignedChannelsByVideo: $assignmentRepo->preloadAssignedChannels($videosOfUploader),
                    preferredChannelIdByVideo: app(PreferredChannelService::class)
                        ->preloadForVideos($videosOfUploader),
                );

                // 6) ValueObject
                $run = new AssignmentRun(
                    groups: $groups,
                    channelPool: $channelPoolDto,
                    videoContext: $videoContext,
                    batch: $batch,
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
            ->flatMap(fn (Video $video) => $blockedByVideo[$video->getKey()] ?? collect())
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

    /**
     * Distribute every group of a run, tallying assigned/skipped/deferred videos.
     *
     * @return array{0:int,1:int,2:int} [assigned, skipped, deferred]
     */
    public function assignGroups(AssignmentRun $run): array
    {
        $assigned = 0;
        $skipped = 0;
        $deferred = 0;

        foreach ($run->groups as $group) {
            $blockedChannelIds = $this->calculateBlockedChannels($group, $run->videoContext->blockedByVideo);

            $outcome = $this->placeGroup($group, $blockedChannelIds, $run);

            match ($outcome) {
                'assigned' => $assigned += $group->count(),
                'deferred' => $deferred += $group->count(),
                default => $skipped += $group->count(),
            };

            if ($outcome === 'assigned' && $run->quotasUsedUp()) {
                break;
            }
        }

        return [$assigned, $skipped, $deferred];
    }

    /**
     * Decide where a single group goes: its preferred channel when that channel
     * is usable, otherwise the weighted round-robin.
     *
     * @param Collection<int,Video> $group
     * @param array<int,int> $blockedChannelIds
     * @return 'assigned'|'deferred'|'skipped'
     */
    private function placeGroup(Collection $group, array $blockedChannelIds, AssignmentRun $run): string
    {
        $preferredChannelId = app(PreferredChannelService::class)
            ->resolveForGroup($group, $run->videoContext->preferredChannelIdByVideo);

        if ($preferredChannelId !== null) {
            $preferredOutcome = $this->assignToPreferredChannel($group, $preferredChannelId, $blockedChannelIds, $run);

            if ($preferredOutcome !== 'fallthrough') {
                return $preferredOutcome;
            }
        }

        return $this->assignViaAlgorithm($group, $blockedChannelIds, $run);
    }

    /**
     * Place a group through the weighted round-robin channel pool.
     *
     * @param Collection<int,Video> $group
     * @param array<int,int> $blockedChannelIds
     * @return 'assigned'|'skipped'
     */
    private function assignViaAlgorithm(Collection $group, array $blockedChannelIds, AssignmentRun $run): string
    {
        $channel = app(ChannelService::class)->pickTargetChannel(
            $group,
            $run->channelPool->rotationPool,
            $run->channelPool->quota,
            $blockedChannelIds,
            $run->videoContext->assignedChannelsByVideo
        );

        // Defensive fallback: pickTargetChannel returns null only in rare
        // races (e.g. a channel removed between pool build and pickup).
        // Not deterministically testable; covered by integration behaviour.
        if (!$channel) {
            return 'skipped';
        }

        $this->assignmentService->assignGroupToChannel($group, $channel, $run);

        return 'assigned';
    }

    /**
     * Try to place a group on its wished channel.
     *
     * @param Collection<int,Video> $group
     * @param array<int,int> $blockedChannelIds
     * @return 'assigned'|'deferred'|'fallthrough'
     *   - 'assigned'    the group was assigned to the wished channel
     *   - 'deferred'    the wished channel is valid but cannot take the group
     *                   right now (quota/block) — retry next run
     *   - 'fallthrough' the wished channel is not usable at all (not in pool)
     *                   or the wish is already (partially) fulfilled — use the algorithm
     */
    private function assignToPreferredChannel(
        Collection $group,
        int $preferredChannelId,
        array $blockedChannelIds,
        AssignmentRun $run
    ): string {
        $channelService = app(ChannelService::class);
        $channel = $channelService->findPooledChannel($run->channelPool->rotationPool, $preferredChannelId);

        if ($channel === null) {
            return 'fallthrough';
        }

        // Any video in the group already sits on the wished channel → the wish
        // is fulfilled or unfulfillable as an atomic group; fall through to the
        // round-robin rather than deferring forever (channelAcceptsGroup would
        // reject the group on the "already assigned" rule every run).
        $anyAlreadyOnPreferred = $group->some(function ($video) use ($preferredChannelId, $run) {
            $assigned = $run->videoContext->assignedChannelsByVideo[$video->getKey()] ?? collect();
            return $assigned->contains($preferredChannelId);
        });
        if ($anyAlreadyOnPreferred) {
            return 'fallthrough';
        }

        $accepts = $channelService->channelAcceptsGroup(
            $channel,
            $group,
            $run->channelPool->quota,
            $blockedChannelIds,
            $run->videoContext->assignedChannelsByVideo
        );

        if (!$accepts) {
            Log::warning(
                'Group {videos} deferred: preferred channel {channel} cannot take it this run (quota/block)',
                [
                    'videos' => $group->map(fn (Video $v) => (int) $v->getKey())->all(),
                    'channel' => $preferredChannelId,
                ]
            );

            return 'deferred';
        }

        $this->assignmentService->assignGroupToChannel($group, $channel, $run, viaPreferred: true);

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

            $bundleIds = $bundleMap->first(fn (Collection $ids) => $ids->contains($video->getKey()));

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
