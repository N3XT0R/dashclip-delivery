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
        private AutoRefreshTokenProvider $tokenProvider
    ) {
    }


    public function uploadFile(
        Filesystem $disk,
        string $relativePath,
        string $targetPath,
        ?ProgressBar $bar = null
    ): void {
        $read = $disk->readStream($relativePath);
        if ($read === false) {
            throw new IOException("Konnte Datei nicht lesen: {$relativePath}");
        }

        rewind($read);
        $bytes = $disk->size($relativePath);
        $root = (string)config('filesystems.disks.dropbox.root', '');
        $targetPath = PathBuilder::forDropbox($root, $targetPath);

        $client = new DropboxClient($this->tokenProvider);
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
            Log::error('Dropbox-Upload: '.$e->getMessage(), [
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
}