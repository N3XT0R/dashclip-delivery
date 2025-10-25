<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Support\PathBuilder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function uploadFile(Filesystem $sourceDisk, string $relativePath, string $targetDisk): void
    {
        if ($targetDisk === 'dropbox') {
            app(DropboxUploadService::class)
                ->uploadFile($sourceDisk, $relativePath, PathBuilder::forDropbox('', $relativePath));
            return;
        }

        Storage::disk($targetDisk)
            ->put($relativePath, $sourceDisk->readStream($relativePath));
    }
}