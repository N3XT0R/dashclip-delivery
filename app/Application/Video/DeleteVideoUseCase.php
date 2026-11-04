<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Exceptions\Video\VideoNotDeletableException;
use App\Models\Video;
use App\Repository\VideoRepository;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a video on behalf of its submitter, the single place for the API and the user area.
 *
 * The deletion rule is checked again on the locked row, so an offer created after the caller's
 * own check cannot slip through. The video is only soft-deleted: its file stays until the
 * video is removed for good.
 */
readonly class DeleteVideoUseCase
{
    public function __construct(
        private VideoRepository $videoRepository,
        private IsDeletableUseCase $isDeletable,
    ) {
    }

    /**
     * @param Video $video
     * @return void
     * @throws VideoNotDeletableException when the video has active or picked-up offers
     */
    public function handle(Video $video): void
    {
        DB::transaction(function () use ($video): void {
            $locked = $this->videoRepository->lockForUpdate((int)$video->getKey());
            if ($locked === null) {
                // already deleted by a concurrent request
                return;
            }

            if (!$this->isDeletable->handle($locked)) {
                throw new VideoNotDeletableException('The video still has active or picked-up offers.');
            }

            $this->videoRepository->delete($locked);
        });
    }
}
