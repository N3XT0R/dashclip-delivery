<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
use App\Enum\Ingest\IngestStepEnum;
use App\Services\Upload\DropboxUploadService;

readonly class UploadVideoToDropboxStep implements IngestStepInterface
{
    public function __construct(
        private DropboxUploadService $uploadService
    ) {
    }

    public function name(): IngestStepEnum
    {
        return IngestStepEnum::UploadVideoToDropbox;
    }

    public function dependsOn(): array
    {
        return [
            IngestStepEnum::LookupAndUpdateVideoHash
        ];
    }

    public function isApplicable(IngestContext $context): bool
    {
        return !$context->isDuplicate
            && !$this->uploadService->exists(
                $context->video->path
            );
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
