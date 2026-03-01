<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Models\Video;
use App\Repository\VideoRepository;
use App\Services\DynamicStorageService;
use Illuminate\Support\Facades\Storage;

readonly class UpdateVideoHash
{

    public function __construct(
        private VideoRepository $videoRepository,
        private DynamicStorageService $dynamicStorageService,
    ) {
    }

    public function handle(Video $video, ?string $hash = null): void
    {
        $disk = Storage::disk($video->disk);

        $hash ??= $this->dynamicStorageService->getHashForFilePath($disk, $video->path);

        $this->videoRepository->update($video, [
            'hash' => $hash,
        ]);
    }
}