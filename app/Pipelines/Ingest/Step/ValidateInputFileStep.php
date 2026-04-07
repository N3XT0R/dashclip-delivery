<?php

declare(strict_types=1);

namespace App\Pipelines\Ingest\Step;

use App\Enum\Ingest\IngestStepEnum;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Repository\VideoRepository;
use Illuminate\Support\Facades\Storage;

class ValidateInputFileStep implements IngestStepInterface
{
    public function __construct(
        protected VideoRepository $videoRepository,
    ) {
    }

    public function name(): IngestStepEnum
    {
        return IngestStepEnum::ValidateInputFile;
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function isApplicable(IngestContext $context): bool
    {
        return !$context->isDuplicate && !$context->isInvalid;
    }

    public function handle(IngestContext $context): IngestContext
    {
        $video = $context->video;

        if (empty($video->disk) || empty($video->path)) {
            $this->videoRepository->delete($video);
            $context->isInvalid = true;

            return $context;
        }

        $disk = Storage::disk($video->disk);

        if (!$disk->exists($video->path)) {
            $this->videoRepository->delete($video);
            $context->isInvalid = true;

            return $context;
        }

        return $context;
    }

}
