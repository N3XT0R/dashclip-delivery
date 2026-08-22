<?php

declare(strict_types=1);

namespace App\Application\Api;

use App\Enum\BatchTypeEnum;
use App\Models\Assignment;
use App\Services\BatchService;

class CreateOfferUseCase
{
    public function __construct(private readonly BatchService $batchService)
    {
    }

    /**
     * Create a manual API offer: wraps the assignment in a fresh batch of type
     * "api" and applies the default expiry TTL.
     */
    public function execute(int $videoId, int $channelId): Assignment
    {
        $batch = $this->batchService->startBatch(BatchTypeEnum::API);

        $offer = new Assignment([
            'video_id' => $videoId,
            'channel_id' => $channelId,
            'batch_id' => $batch->getKey(),
        ]);
        $offer->setExpiresAt();
        $offer->save();

        return $offer;
    }
}
