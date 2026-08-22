<?php

declare(strict_types=1);

namespace App\Application\Offer;

use App\Enum\BatchTypeEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Services\BatchService;

readonly class CreateOfferUseCase
{
    public function __construct(
        private BatchService $batchService,
        private AssignmentRepository $assignmentRepository,
    ) {
    }

    /**
     * Create a manual API offer: wraps the assignment in a fresh batch of type
     * "api" and applies the default expiry TTL.
     */
    public function handle(Video $video, Channel $channel): Assignment
    {
        $batch = $this->batchService->startBatch(BatchTypeEnum::API);

        $offer = $this->assignmentRepository->createAssignment($video, $channel, $batch);
        $offer->setExpiresAt();
        $offer->save();

        return $offer;
    }
}
