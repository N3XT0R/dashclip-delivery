<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
use App\Enum\Ingest\IngestStepEnum;
use App\Repository\ClipRepository;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Storage;

/**
 * This step generates preview images for video clips and updates the clip records with the preview paths.
 * It depends on the LookupAndUpdateVideoHash step to ensure that the video hash is available before processing clips.
 */
readonly class GeneratePreviewForVideoClipsStep implements IngestStepInterface
{
    public function __construct(
        private PreviewService $previewService,
        private ClipRepository $clipRepository,
    ) {
    }

    public function name(): IngestStepEnum
    {
        return IngestStepEnum::GeneratePreviewForVideoClips;
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
            && $context->clips !== null
            && $context->clips->isNotEmpty();
    }

    public function handle(IngestContext $context): IngestContext
    {
        if ($context->isDuplicate || null === $context->clips || $context->clips->isEmpty()) {
            return $context;
        }

        $diskName = config('preview.default_disk', 'preview');
        $previewDisk = Storage::disk($diskName);

        foreach ($context->clips as $clip) {
            $relativePath = $this->previewService->generatePreviewForClip(
                $clip,
                $previewDisk
            );

            $this->clipRepository->update($clip, [
                'preview_path' => $relativePath,
                'preview_disk' => $diskName,
            ]);

            $clip->preview_path = $relativePath;
            $clip->preview_disk = $diskName;
        }

        return $context;
    }
}
