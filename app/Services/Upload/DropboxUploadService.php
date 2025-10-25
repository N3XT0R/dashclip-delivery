<?php

declare(strict_types=1);

namespace App\Services\Upload;

use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

class DropboxUploadService
{
    private const CHUNK_SIZE = 8 * 1024 * 1024; // 8 MB

    public function __construct(
        private AutoRefreshTokenProvider $tokenProvider
    ) {
    }


    public function uploadFile(
        Filesystem $disk,
        string $relativePath,
        string $dstRel,
        ?ProgressBar $bar = null
    ): bool {
        $root = (string)config('filesystems.disks.dropbox.root', '');
        $read = $disk->readStream($relativePath);
        $bytes = $disk->size($relativePath);

        if ($read === false) {
            throw new RuntimeException("Konnte Datei nicht lesen: {$relativePath}");
        }


        return true;
    }
}