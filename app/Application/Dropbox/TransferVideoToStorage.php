<?php

declare(strict_types=1);

namespace App\Application\Dropbox;

use App\Models\Video;
use App\Services\Upload\DropboxUploadService;

/**
 * This class is responsible for uploading a video file to Dropbox.
 * It uses the DropboxUploadService to perform the actual upload.
 */
readonly class TransferVideoToStorage
{
    public function __construct(
        private DropboxUploadService $uploadService
    ) {
    }

    /**
     * Handle the upload of a video to Dropbox.
     * @param Video $video
     * @return void
     * @throws \Throwable
     */
    public function handle(Video $video): void
    {
        $sourceDisk = $video->getDisk();
        $originPath = $video->path;
        $this->uploadService->uploadFile(
            sourceDisk: $sourceDisk,
            relativePath: $originPath,
            targetPath: $originPath
        );
    }
}
