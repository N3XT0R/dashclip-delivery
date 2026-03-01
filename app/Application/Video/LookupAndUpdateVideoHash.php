<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Models\Video;
use App\Repository\VideoRepository;
use App\Services\DynamicStorageService;
use App\Services\VideoService;
use Illuminate\Support\Facades\Storage;

readonly class LookupAndUpdateVideoHash
{

    public function __construct(
        private VideoRepository $videoRepository,
        private VideoService $videoService,
        private DynamicStorageService $dynamicStorageService,
    ) {
    }

    public function handle(Video $video, ?string $hash = null): void
    {
        $disk = Storage::disk($video->disk);
        $hash ??= $this->dynamicStorageService->getHashForFilePath($disk, $video->path);
        if ($this->videoService->isDuplicate($hash)) {
            $this->videoService->deleteDuplicateVideo($video);
            return;
        }

        $this->videoRepository->update($video, [
            'hash' => $hash,
        ]);
    }
}