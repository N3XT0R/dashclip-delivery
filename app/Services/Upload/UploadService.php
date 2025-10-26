<?php

declare(strict_types=1);

namespace App\Services\Upload;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function uploadFile(
        Filesystem $sourceDisk,
        string $relativePath,
        string $targetDisk,
        string $targetPath
    ): void {
        if ($targetDisk === 'dropbox') {
            $this->uploadToDropbox($sourceDisk, $relativePath, $targetPath);
            return;
        }

        Storage::disk($targetDisk)
            ->put($targetPath, $sourceDisk->readStream($relativePath));
    }

    protected function uploadToDropbox(Filesystem $disk, string $relativePath, string $dstRel): void
    {
        app(DropboxUploadService::class)
            ->uploadFile($disk, $relativePath, $dstRel);
    }
}