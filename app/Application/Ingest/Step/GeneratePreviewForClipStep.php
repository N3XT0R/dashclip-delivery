<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
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

    public function name(): string
    {
        return 'generate_preview_for_clip';
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
