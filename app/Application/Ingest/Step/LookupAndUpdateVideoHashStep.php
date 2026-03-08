<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
use App\Repository\VideoRepository;
use App\Services\DynamicStorageService;
use App\Services\VideoService;
use Illuminate\Support\Facades\Storage;

readonly class LookupAndUpdateVideoHashStep implements IngestStepInterface
{
    public function __construct(
        private VideoRepository $videoRepository,
        private VideoService $videoService,
        private DynamicStorageService $dynamicStorageService,
    ) {
    }

    public function name(): string
    {
        return 'lookup_and_update_video_hash';
    }

    public function isApplicable(IngestContext $context): bool
    {
        if ($context->isDuplicate) {
            return false;
        }

        if (!empty($context->video->hash)) {
            return false;
        }

        return true;
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function handle(IngestContext $context): IngestContext
    {
        if ($context->video->hash) {
            return $context;
        }
        
        $video = $context->video;
        $disk = Storage::disk($video->disk);

        $hash = $context->hash
            ?? $this->dynamicStorageService->getHashForFilePath($disk, $video->path);

        $context->hash = $hash;

        if ($this->videoService->isDuplicate($hash)) {
            $this->videoService->deleteDuplicateVideo($video);
            $context->isDuplicate = true;

            return $context;
        }

        $this->videoRepository->update($video, [
            'hash' => $hash,
        ]);

        return $context;
    }
}
