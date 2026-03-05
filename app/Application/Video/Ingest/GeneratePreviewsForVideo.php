<?php

declare(strict_types=1);

namespace App\Application\Video\Ingest;

use App\Models\Video;
use App\Services\PreviewService;

readonly class GeneratePreviewsForVideo
{
    public function __construct(
        private PreviewService $previewService
    ) {
    }

    public function handle(Video $video): bool
    {
        return true;
    }
}