<?php

declare(strict_types=1);

namespace App\Pipelines\Ingest\Step;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\Ingest\IngestStepEnum;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Repository\VideoRepository;
use App\Services\Contracts\ConfigServiceInterface;
use App\Services\Storage\StorageDiskService;
use App\Services\Upload\UploadService;
use Illuminate\Support\Facades\Storage;

/**
 * Moves a newly ingested video to the storage disk set in the default_file_system setting.
 *
 * The class and step name still say Dropbox because the step name is stored in every video's
 * ingest state; renaming it would mark the step as missing for all existing videos.
 * Only configured remote disks are targets, so a local value keeps the video where it was uploaded.
 */
readonly class UploadVideoToDropboxStep implements IngestStepInterface
{
    public function __construct(
        private UploadService $uploadService,
        private ConfigServiceInterface $configService,
        private VideoRepository $videoRepository,
        private StorageDiskService $disks,
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
        $target = $this->targetDisk();
        if ($target === null || $context->video->disk === $target) {
            return false;
        }

        return false === $context->isDuplicate
            && false === $context->isInvalid
            && false === Storage::disk($target)->exists($context->video->path);
    }

    public function handle(IngestContext $context): IngestContext
    {
        if ($context->isDuplicate) {
            return $context;
        }
        $target = $this->targetDisk();
        if ($target === null) {
            return $context;
        }

        $video = $context->video;
        $sourceDisk = clone $video->getDisk();
        $path = $video->path;

        $this->uploadService->uploadFile(
            sourceDisk: $sourceDisk,
            relativePath: $path,
            targetDisk: $target,
            targetPath: $path
        );

        $video->disk = $target;
        if ($this->videoRepository->save($video)) {
            $sourceDisk->delete($path);
        }

        return $context;
    }

    /** The configured remote disk new videos belong on, or null to keep them where they were uploaded. */
    private function targetDisk(): ?string
    {
        $target = (string)$this->configService->get(
            DefaultConfigEntry::DEFAULT_FILE_SYSTEM,
            'default',
            'local'
        );

        return $this->disks->isConfigured($target) && !$this->disks->isLocal($target) ? $target : null;
    }
}
