<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Facades\PathBuilder;
use App\Services\Dropbox\AutoRefreshTokenProvider;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Spatie\Dropbox\Client as DropboxClient;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Exception\IOException;

class DropboxUploadService
{
    private const CHUNK_SIZE = 8 * 1024 * 1024; // 8 MB

    public function __construct(
        private AutoRefreshTokenProvider $tokenProvider,
        private ?DropboxClient $client = null
    ) {
    }

    private function getClient(): DropboxClient
    {
        return $this->client ??= new DropboxClient($this->tokenProvider);
    }


    /**
     * Upload a file to Dropbox using chunked upload for large files.
     * @param Filesystem $sourceDisk
     * @param string $relativePath
     * @param string $targetPath
     * @param ProgressBar|null $bar
     * @return void
     * @throws \Throwable
     */
    public function uploadFile(
        Filesystem $sourceDisk,
        string $relativePath,
        string $targetPath,
        ?ProgressBar $bar = null
    ): void {
        $read = $sourceDisk->readStream($relativePath);
        if ($read === false) {
            throw new IOException("Unable to read file: {$relativePath}");
        }

        rewind($read);
        $bytes = $sourceDisk->size($relativePath);
        $root = (string)config('filesystems.disks.dropbox.root', '');
        $targetPath = PathBuilder::forDropbox($root, $targetPath);

        $client = $this->getClient();
        $cursor = null;

        try {
            // Edge case: empty file
            if ($bytes === 0) {
                $client->upload($targetPath, '');
                return;
            }

            $chunkSize = self::CHUNK_SIZE;

            /**
             * IMPORTANT:
             * upload file direct if filesize smaller than chunk-size
             */
            if ($bytes <= $chunkSize) {
                $content = stream_get_contents($read);
                $meta = $client->upload($targetPath, $content);
                Log::info('Dropbox-Upload direct finished', ['meta' => $meta]);
                return;
            }

            $firstChunk = fread($read, $chunkSize) ?: '';
            $cursor = $client->uploadSessionStart($firstChunk);
            $bar?->advance(strlen($firstChunk));

            $transferred = strlen($firstChunk);

            while (!feof($read)) {
                $chunk = fread($read, $chunkSize) ?: '';
                if ($chunk === '' || $chunk === false) {
                    break; // safety break on empty chunk
                }
                $len = strlen($chunk);
                $transferred += $len;

                if ($transferred >= $bytes) {
                    // Last chunk
                    $meta = $client->uploadSessionFinish($chunk, $cursor, $targetPath);
                    Log::info('Dropbox-Upload session finished', ['meta' => $meta]);
                } else {
                    // Append with explicit offset
                    $cursor = $client->uploadSessionAppend($chunk, $cursor);
                }

                $bar?->advance(strlen($chunk));
            }
        } catch (\Throwable $e) {
            Log::error('Dropbox-Upload: ' . $e->getMessage(), [
                'session' => $cursor?->session_id,
                'exception' => $e
            ]);
            throw $e;
        } finally {
            Log::info('Dropbox-Upload completed', [
                'path' => $targetPath,
                'bytes' => $bytes,
                'relativePath' => $relativePath,
                'session' => $cursor?->session_id,
            ]);
            fclose($read);
            $bar?->finish();
        }
    }

    /**
     * Delete a file from Dropbox.
     * @param string $targetPath
     * @return void
     * @throws \Throwable
     */
    public function deleteFile(string $targetPath): void
    {
        $root = (string)config('filesystems.disks.dropbox.root', '');
        $targetPath = PathBuilder::forDropbox($root, $targetPath);

        try {
            $client = $this->getClient();
            $client->delete($targetPath);
            Log::info('Dropbox upload: file deleted', ['path' => $targetPath]);
        } catch (\Throwable $e) {
            Log::error('Dropbox upload: error deleting file: ' . $e->getMessage(), [
                'path' => $targetPath,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Check if a file exists in Dropbox.
     * @param string $targetPath
     * @return bool
     * @throws \Throwable
     */
    public function exists(string $targetPath): bool
    {
        $root = (string)config('filesystems.disks.dropbox.root', '');
        $targetPath = PathBuilder::forDropbox($root, $targetPath);

        try {
            $client = $this->getClient();
            $client->getMetadata($targetPath);
            return true;
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'not_found')) {
                return false; // file does not exist
            }
            Log::error('Dropbox upload: error while checking file: ' . $e->getMessage(), [
                'path' => $targetPath,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Get a temporary link for a file in Dropbox.
     * @param string $targetPath
     * @return string
     * @throws \Throwable
     */
    public function getTemporaryLink(string $targetPath): string
    {
        $root = (string)config('filesystems.disks.dropbox.root', '');
        $targetPath = PathBuilder::forDropbox($root, $targetPath);

        try {
            return $this->getClient()->getTemporaryLink($targetPath);
        } catch (\Throwable $e) {
            Log::error('Dropbox upload: error retrieving temporary link: ' . $e->getMessage(), [
                'path' => $targetPath,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
