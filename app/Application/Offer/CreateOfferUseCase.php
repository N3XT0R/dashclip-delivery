<?php

declare(strict_types=1);

namespace App\Application\Offer;

use App\Enum\BatchTypeEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Services\BatchService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     *
     * Batch and assignment creation are wrapped in a single transaction so a
     * race that still hits the assignments video_id/channel_id unique index
     * cannot leave an orphaned batch behind. Such a race is translated into the
     * same 422 the StoreOfferRequest returns when the duplicate is caught up
     * front, instead of surfacing a database error.
     *
     * @throws ValidationException when an offer for this pair already exists
     */
    public function handle(Video $video, Channel $channel): Assignment
    {
        try {
            return DB::transaction(function () use ($video, $channel): Assignment {
                $batch = $this->batchService->startBatch(BatchTypeEnum::API);

                $offer = $this->assignmentRepository->createAssignment($video, $channel, $batch);
                $offer->setExpiresAt();
                $offer->save();

                return $offer;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'video_id' => 'An offer for this video and channel already exists.',
            ]);
        }
    }
}
