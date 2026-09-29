<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Models\Video;
use App\Repository\ClipRepository;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Storage;

/**
 * Cuts the previews of a video again from the file that is handed out.
 *
 * Whenever that file changes, the previews have to follow: a preview made from the untouched
 * original would still show the plates that the blurred version hides.
 */
readonly class PreviewRefresher
{
    public function __construct(
        private PreviewService $previewService,
        private ClipRepository $clipRepository,
    ) {
    }

    public function refresh(Video $video): void
    {
        $diskName = (string)config('preview.default_disk', 'public');
        $previewDisk = Storage::disk($diskName);

        foreach ($video->clipsWithTrashed()->get() as $clip) {
            $path = $this->previewService->generatePreviewForClip($clip, $previewDisk, force: true);

            $this->clipRepository->update($clip, [
                'preview_path' => $path,
                'preview_disk' => $diskName,
            ]);
        }
    }
}
