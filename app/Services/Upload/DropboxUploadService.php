<?php

declare(strict_types=1);

namespace App\Services\Upload;

use App\Facades\PathBuilder;
use App\Services\Dropbox\AutoRefreshTokenProvider;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Utils;
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
        return $this->client ??= new DropboxClient(
            $this->tokenProvider,
            new GuzzleClient(['handler' => GuzzleFactory::handler(), 'stream' => true]),
        );
    }


    /**
     * Upload a file to Dropbox using chunked upload for large files.
     * @param  Filesystem  $sourceDisk
     * @param  string  $relativePath
     * @param  string  $targetPath
     * @param  ProgressBar|null  $bar
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
        $stream = Utils::streamFor($read);

        try {
            if ($bytes === 0) {
                $client->upload($targetPath, '');
                return;
            }

            // Always use the upload-session API so upload() — which calls
            // fstat() and only accepts string|resource — is never touched.
            $prevTell = 0;

            while (!$stream->eof()) {
                $offset   = $stream->tell();
                $chunk    = new LimitStream($stream, self::CHUNK_SIZE, $offset);
                $cursor   = $cursor === null
                    ? $client->uploadSessionStart($chunk)
                    : $client->uploadSessionAppend($chunk, $cursor);

                $bar?->advance($stream->tell() - $prevTell);
                $prevTell = $stream->tell();
            }

            $meta = $client->uploadSessionFinish('', $cursor, $targetPath);
            Log::info('Dropbox-Upload session finished', ['meta' => $meta]);
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
            $stream->close();
            $bar?->finish();
        }
    }

    /**
     * Delete a file from Dropbox.
     * @param  string  $targetPath
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
            Log::error('Dropbox upload: error deleting file: '.$e->getMessage(), [
                'path' => $targetPath,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Check if a file exists in Dropbox.
     * @param  string  $targetPath
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
            Log::error('Dropbox upload: error while checking file: '.$e->getMessage(), [
                'path' => $targetPath,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Get a temporary link for a file in Dropbox.
     * @param  string  $targetPath
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
            Log::error('Dropbox upload: error retrieving temporary link: '.$e->getMessage(), [
                'path' => $targetPath,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
