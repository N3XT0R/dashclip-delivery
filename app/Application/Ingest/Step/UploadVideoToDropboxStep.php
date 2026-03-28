<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
use App\Constants\Config\DefaultConfigEntry;
use App\Enum\Ingest\IngestStepEnum;
use App\Repository\VideoRepository;
use App\Services\Contracts\ConfigServiceInterface;
use App\Services\Upload\DropboxUploadService;

readonly class UploadVideoToDropboxStep implements IngestStepInterface
{
    public function __construct(
        private DropboxUploadService $uploadService,
        private ConfigServiceInterface $configService,
        private VideoRepository $videoRepository,
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
        return false === $context->isDuplicate
            && false === $this->uploadService->exists(
                $context->video->path
            );
    }

    public function handle(IngestContext $context): IngestContext
    {
        if ($context->isDuplicate) {
            return $context;
        }

        $video = $context->video;
        $defaultFileSystem = (string)$this->configService->get(
            DefaultConfigEntry::DEFAULT_FILE_SYSTEM,
            'default',
            'local'
        );

        if ($defaultFileSystem !== 'dropbox') {
            return $context;
        }

        $this->uploadService->uploadFile(
            sourceDisk: $video->getDisk(),
            relativePath: $video->path,
            targetPath: $video->path
        );

        $video->disk = 'dropbox';
        $this->videoRepository->save($video);

        return $context;
    }
}
