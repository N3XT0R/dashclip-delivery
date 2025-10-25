<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Facades\PathBuilder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function uploadFile(Filesystem $sourceDisk, string $relativePath, string $targetDisk): void
    {
        if ($targetDisk === 'dropbox') {
            $this->uploadToDropbox($sourceDisk, $relativePath, $relativePath);
            return;
        }

        Storage::disk($targetDisk)
            ->put($relativePath, $sourceDisk->readStream($relativePath));
    }

    protected function uploadToDropbox(Filesystem $disk, string $relativePath, string $dstRel): void
    {
        app(DropboxUploadService::class)
            ->uploadFile($disk, $relativePath, PathBuilder::forDropbox('', $dstRel));
    }
}