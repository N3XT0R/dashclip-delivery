<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\FileInfoDto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DynamicStorageService
{
    public function fromPath(string $path): Filesystem
    {
        $root = is_dir($path) ? $path : dirname($path);
        $this->assertDirectory($root);

        return Storage::build([
            'driver' => 'local',
            'root' => realpath($root) ?: $root,
        ]);
    }

    private function assertDirectory(string $path): void
    {
        if (!is_dir($path)) {
            throw new \RuntimeException("Inbox fehlt: {$path}");
        }
    }

    /**
     * Listet rekursiv alle Dateien als DTOs.
     */
    public function listFiles(Filesystem $disk, string $basePath = ''): Collection
    {
        return collect($disk->allFiles($basePath))
            ->map(fn(string $path) => FileInfoDto::fromPath($path));
    }


    public function getHashForFile(Filesystem $disk, FileInfoDto $file): string
    {
        $stream = $disk->readStream($file->path);
        if ($stream === false) {
            throw new \RuntimeException("Konnte Datei nicht lesen: {$file->path}");
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        $hash = hash_final($context);

        fclose($stream);
        return $hash;
    }
}