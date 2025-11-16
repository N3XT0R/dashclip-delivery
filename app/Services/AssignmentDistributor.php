<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\StatusEnum;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Repository\BatchRepository;
use RuntimeException;

/**
 * Service to distribute videos across channels based on quotas, weights, and blocks.
 * @todo refactor to smaller services?
 */
class AssignmentDistributor
{

    /**
     * Distribute new and requeueable videos across channels.
     *
     * @param  int|null  $quotaOverride  optional quota per channel
     * @return array{assigned:int, skipped:int}
     */
    public function distribute(?int $quotaOverride = null): array
    {
        $channelService = app(ChannelService::class);
        $batchService = app(BatchService::class);
        $batchRepo = app(BatchRepository::class);
        $assignmentRepo = app(AssignmentRepository::class);
        $batch = $batchService->startBatch();

        $lastFinished = $batchRepo->getLastFinishedAssignBatch();

        // 1) Kandidaten einsammeln (neu, unzugewiesen, requeue)
        $poolVideos = $batchService->collectPoolVideos($lastFinished);

        if ($poolVideos->isEmpty()) {
            $batchRepo->markAssignedBatchAsFinished($batch, 0, 0);
            throw new RuntimeException('Nichts zu verteilen.');
        }

        // 2) Bundles vollständig machen
        $poolVideos = $assignmentRepo->expandBundles($poolVideos)->values();

        // 3) Kanäle + Rotationspool + Quotas
        $channelPoolDto = $channelService->prepareChannelsAndPool($quotaOverride);

        if ($channelPoolDto->channels->isEmpty()) {
            $batchRepo->markAssignedBatchAsFinished($batch, 0, 0);
            throw new RuntimeException('Keine Kanäle konfiguriert.');
        }

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
            $blockedChannelIds = $group
                ->flatMap(fn(Video $v) => $blockedByVideo[$v->getKey()] ?? collect())
                ->unique()
                ->all();

            $quota = $channelPoolDto->quota;

            $target = $channelService->pickTargetChannel(
                $group,
                $channelPoolDto->rotationPool,
                $quota,
                $blockedChannelIds,
                $assignedChannelsByVideo
            );

            if (!$target) {
                $skipped += $group->count();
                continue;
            }

            foreach ($group as $video) {
                $assignmentRepo->create([
                    'video_id' => $video->getKey(),
                    'channel_id' => $target->getKey(),
                    'batch_id' => $batch->getKey(),
                    'status' => StatusEnum::QUEUED->value,
                ]);

                // Für Folgerunden merken, dass dieses Video diesem Kanal nun zugeordnet ist
                $assignedChannelsByVideo[$video->getKey()] = ($assignedChannelsByVideo[$video->getKey()] ?? collect())
                    ->push($target->getKey())
                    ->unique();

                $quota[$target->getKey()] -= 1;
                $assigned++;
            }

            // Abbruch, wenn alle Quotas aufgebraucht sind
            if (collect($quota)->every(fn(int $q) => $q <= 0)) {
                break;
            }
        }


        $batchRepo->markAssignedBatchAsFinished($batch, $assigned, $skipped);

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }
}
