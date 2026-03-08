<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
use App\Enum\Ingest\IngestStepEnum;
use App\Repository\ClipRepository;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Storage;

readonly class GeneratePreviewForClipStep implements IngestStepInterface
{
    public function __construct(
        private PreviewService $previewService,
        private ClipRepository $clipRepository,
    ) {
    }

    public function step(): IngestStepEnum
    {
        return IngestStepEnum::GeneratePreviewForClip;
    }

    public function dependsOn(): array
    {
        return [
            IngestStepEnum::LookupAndUpdateVideoHash
        ];
    }

    public function isApplicable(IngestContext $context): bool
    {
        return !$context->isDuplicate && null !== $context->clip;
    }

    public function handle(IngestContext $context): IngestContext
    {
        if ($context->isDuplicate) {
            return $context;
        }

        if (!$context->clip) {
            return $context;
        }

        $diskName = config('preview.default_disk', 'preview');
        $previewDisk = Storage::disk($diskName);

        $relativePath = $this->previewService->generatePreviewForClip(
            $context->clip,
            $previewDisk
        );

        $this->clipRepository->update($context->clip, [
            'preview_path' => $relativePath,
            'preview_disk' => $diskName,
        ]);

        return $context;
    }
}
