<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\BatchTypeEnum;
use App\Repository\BatchRepository;
use App\Repository\DownloadRepository;
use App\Repository\VideoRepository;
use Illuminate\Support\Carbon;

readonly class VideoCleanupService
{
    public function __construct(
        private DownloadRepository $downloadRepository,
        private VideoRepository $videoRepository,
        private BatchRepository $batchRepository
    ) {
    }

    public function cleanup(int $subWeeks = 1): int
    {
        $batchRepository = $this->batchRepository;
        $downloadRepository = $this->downloadRepository;
        $videoRepository = $this->videoRepository;
        $batch = $batchRepository->create([
            'type' => BatchTypeEnum::REMOVE->value,
            'started_at' => now(),
        ]);

        $threshold = Carbon::now()->subWeeks($subWeeks);
        $candidates = $downloadRepository->fetchDownloadedVideoIds($threshold);
        $deletable = $videoRepository->filterDeletableVideoIds($candidates, $threshold);
        $names = $videoRepository->fetchOriginalNames($deletable);
        $deleted = $videoRepository->deleteVideosByIds($deletable);

        $this->batchRepository->update($batch, [
            'finished_at' => now(),
            'stats' => [
                'removed' => $deleted,
                'original_names' => $names->values()->all(),
            ],
        ]);

        return $deleted;
    }
}

