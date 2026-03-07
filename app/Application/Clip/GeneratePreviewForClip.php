<?php

declare(strict_types=1);

namespace App\Application\Clip;

use App\Models\Clip;
use App\Repository\ClipRepository;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Storage;

readonly class GeneratePreviewForClip
{
    public function __construct(
        private PreviewService $previewService,
        private ClipRepository $clipRepository,
    ) {
    }

    public function handle(Clip $clip): bool
    {
        $diskName = config('preview.default_disk', 'preview');
        $previewDisk = Storage::disk($diskName);
        $relativePath = $this->previewService->generatePreviewForClip($clip, $previewDisk);
    }
}
