<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\FileInfoDto;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DynamicStorageService
{
    /**
     * Create a filesystem disk from the given path.
     * @param  string  $path
     * @return Filesystem
     */
    public function fromPath(string $path): Filesystem
    {
        $root = is_dir($path) ? $path : dirname($path);
        $this->assertDirectory($root);

        return Storage::build([
            'driver' => 'local',
            'root' => realpath($root) ?: $root,
        ]);
    }

    /**
     * Assert that the given path is a directory.
     * @param  string  $path
     * @return void
     */
    private function assertDirectory(string $path): void
    {
        if (!is_dir($path)) {
            throw new \RuntimeException("Inbox fehlt: {$path}");
        }
    }

    /**
     * List all files in the given disk and base path.
     * @param  Filesystem  $disk
     * @param  string  $basePath
     * @return Collection
     */
    public function listFiles(Filesystem $disk, string $basePath = ''): Collection
    {
        return collect($disk->allFiles($basePath))
            ->map(fn(string $path) => FileInfoDto::fromPath($path));
    }

    /**
     * Get the hash for the given FileInfoDto.
     * @param  Filesystem  $disk
     * @param  FileInfoDto  $file
     * @return string
     */
    public function getHashForFileInfoDto(Filesystem $disk, FileInfoDto $file): string
    {
        return $this->getHashForFilePath($disk, $file->path);
    }

    /**
     * Get the hash for the given file path.
     * @param  Filesystem  $disk
     * @param  string  $relativePath
     * @return string
     */
    public function getHashForFilePath(Filesystem $disk, string $relativePath): string
    {
        $stream = $disk->readStream($relativePath);
        if (!is_resource($stream)) {
            throw new \RuntimeException("Konnte Datei nicht lesen: {$relativePath}");
        }

        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        $hash = hash_final($context);

        fclose($stream);
        return $hash;
    }
}