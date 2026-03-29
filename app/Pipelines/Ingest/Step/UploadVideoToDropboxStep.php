<?php

declare(strict_types=1);

namespace App\Pipelines\Ingest\Step;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\Ingest\IngestStepEnum;
use App\Pipelines\Ingest\Context\IngestContext;
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
        $defaultFileSystem = (string)$this->configService->get(
            DefaultConfigEntry::DEFAULT_FILE_SYSTEM,
            'default',
            'local'
        );

        if ($defaultFileSystem !== 'dropbox') {
            return false;
        }

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
        $sourceDisk = clone $video->getDisk();
        $path = $video->path;

        $this->uploadService->uploadFile(
            sourceDisk: $sourceDisk,
            relativePath: $path,
            targetPath: $path
        );

        $video->disk = 'dropbox';
        if ($this->videoRepository->save($video)) {
            $sourceDisk->delete($path);
        }

        return $context;
    }
}
