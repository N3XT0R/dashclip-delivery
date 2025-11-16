<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ChannelPoolDto;
use App\Enum\BatchTypeEnum;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\ValueObjects\AssignmentRun;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Service to distribute videos across channels based on quotas, weights, and blocks.
 * @todo refactor to smaller services?
 */
class AssignmentDistributor
{

    public function __construct(
        private AssignmentRepository $assignmentRepository,
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
        $batch = $batchService->startBatch(BatchTypeEnum::ASSIGN);
        // 1) Kandidaten einsammeln (neu, unzugewiesen, requeue)
        $poolVideos = $this->collectPoolOrAbort($batch);

        // 2) Bundles vollständig machen
        $poolVideos = $assignmentRepo->expandBundles($poolVideos)->values();

        // 3) Kanäle + Rotationspool + Quotas
        $channelPoolDto = $this->prepareChannelsOrAbort($quotaOverride, $batch);

        // 4) Gruppenbildung (Videos, die zu einem Bundle gehören, bleiben zusammen)
        $groups = $assignmentRepo->buildGroups($poolVideos);

        // 5) Preloads zur Minimierung von N+1
        $blockedByVideo = $assignmentRepo->preloadActiveBlocks($poolVideos);
        $assignedChannelsByVideo = $assignmentRepo->preloadAssignedChannels($poolVideos);

        $run = new AssignmentRun(
            groups: $groups,
            channelPool: $channelPoolDto,
            blockedByVideo: $blockedByVideo,
            assignedChannelsByVideo: $assignedChannelsByVideo,
            batch: $batch
        );


        // 6) Verteilung
        [$assigned, $skipped] = $this->assignGroups($run);
        $batchService->finishAssignBatch($batch, $assigned, $skipped);

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }


    private function calculateBlockedChannels(Collection $group, $blockedByVideo): array
    {
        return $group
            ->flatMap(fn(Video $video) => $blockedByVideo[$video->getKey()] ?? collect())
            ->unique()
            ->all();
    }

    private function collectPoolOrAbort($batch): Collection
    {
        $poolVideos = $this->batchService->collectVideosForAssign();

        if ($poolVideos->isEmpty()) {
            $this->batchService->finishAssignBatch($batch, 0, 0);
            throw new RuntimeException('Nichts zu verteilen.');
        }

        return $poolVideos;
    }

    private function prepareChannelsOrAbort(?int $quotaOverride, $batch): ChannelPoolDto
    {
        $channelService = app(ChannelService::class);
        $channelPoolDto = $channelService->prepareChannelsAndPool($quotaOverride);

        if ($channelPoolDto->channels->isEmpty()) {
            $this->batchService->finishAssignBatch($batch, 0, 0);
            throw new RuntimeException('Keine Kanäle konfiguriert.');
        }

        return $channelPoolDto;
    }

    private function assignGroups(AssignmentRun $run): array
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

            [$assigned] = $assignmentService->assignGroupToChannel(
                $group,
                $channel,
                $run
            );
        }

        return [$assigned, $skipped];
    }

}
