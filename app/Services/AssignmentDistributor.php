<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ChannelPoolDto;
use App\Enum\BatchTypeEnum;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Repository\BatchRepository;
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
        $channelService = app(ChannelService::class);
        $batchService = $this->batchService;
        $batchRepo = app(BatchRepository::class);
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

        // 6) Verteilung
        $assigned = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            // Blockierte Kanäle für diese Gruppe ermitteln (union über alle Videos der Gruppe)
            $blockedChannelIds = $this->calculateBlockedChannels($group, $blockedByVideo);

            // B) Zielkanal bestimmen → Delegiert an ChannelService (Domain-Logic separat)
            $channel = $channelService->pickTargetChannel(
                $group,
                $channelPoolDto->rotationPool,
                $channelPoolDto->quota,
                $blockedChannelIds,
                $assignedChannelsByVideo
            );

            if (!$channel) {
                $skipped += $group->count();
                continue;
            }

            foreach ($group as $video) {
                $videoId = $video->getKey();
                $channelId = $channel->getKey();
                $assignmentRepo->createAssignment($video, $channel, $batch);

                // Für Folgerunden merken, dass dieses Video diesem Kanal nun zugeordnet ist
                $assignedChannelsByVideo[$videoId] =
                    ($assignedChannelsByVideo[$videoId] ?? collect())
                        ->push($channelId)
                        ->unique();

                $channelPoolDto->quota[$channelId]--;
                $assigned++;
            }

            // Abbruch, wenn alle Quotas aufgebraucht sind
            if ($this->allQuotasUsedUp($channelPoolDto->quota)) {
                break;
            }
        }


        $batchRepo->markAssignedBatchAsFinished($batch, $assigned, $skipped);

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }


    private function calculateBlockedChannels(Collection $group, $blockedByVideo): array
    {
        return $group
            ->flatMap(fn(Video $video) => $blockedByVideo[$video->getKey()] ?? collect())
            ->unique()
            ->all();
    }


    private function allQuotasUsedUp(array $quota): bool
    {
        return collect($quota)->every(fn(int $q) => $q <= 0);
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
}
