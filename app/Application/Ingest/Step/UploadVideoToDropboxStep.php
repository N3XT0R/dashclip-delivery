<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
use App\Services\Upload\DropboxUploadService;

readonly class UploadVideoToDropboxStep implements IngestStepInterface
{
    public function __construct(
        private DropboxUploadService $uploadService
    ) {
    }

    public function name(): string
    {
        return 'upload_video_to_dropbox';
    }

    public function dependsOn(): array
    {
        return ['lookup_and_update_video_hash'];
    }

    public function handle(IngestContext $context): IngestContext
    {
        if ($context->isDuplicate) {
            return $context;
        }

        $video = $context->video;

        $this->uploadService->uploadFile(
            sourceDisk: $video->getDisk(),
            relativePath: $video->path,
            targetPath: $video->path
        );

        return $context;
    }
}
