<?php

declare(strict_types=1);

namespace App\Services\Zip;

use App\Enum\DownloadStatusEnum;
use App\Exceptions\Zip\ZipBuildException;
use App\Models\{Assignment, Batch, Channel, Video};
use App\Services\CsvService;
use App\Services\DownloadCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;
use Spatie\FlysystemDropbox\DropboxAdapter;
use ZipArchive;

class ZipService
{
    /** Bytes copied from remote storage between two progress updates. */
    public const TRANSFER_CHUNK_BYTES = 8 * 1024 * 1024;

    public function __construct(
        private DownloadCacheService $cache,
        private CsvService $csvService
    ) {
    }

    /**
     *
     * @param Batch|null $batch
     * @param Channel $channel
     * @param Collection<Assignment> $items
     * @param string $ip
     * @param string|null $userAgent
     * @param string|null $jobId
     * @return string
     */
    public function build(
        ?Batch $batch,
        Channel $channel,
        Collection $items,
        string $ip,
        ?string $userAgent,
        ?string $jobId = null
    ): string {
        if ($batch && $jobId === null) {
            $jobId = $this->jobId($batch, $channel);
        }

        $downloadName = $this->downloadName($batch, $channel);
        if ($items->isEmpty()) {
            throw new ZipBuildException('No downloadable videos remain in this selection.');
        }
        $tmpPath = $this->zipPath($jobId);

        $this->prepareDirectories();

        // remember assignments for later download tracking
        $this->cache->setAssignments($jobId, $items->pluck('id')->all());

        $zip = $this->createZipArchive($tmpPath, $items);
        $tmpFiles = [];

        try {
            $this->cache->setStatus($jobId, DownloadStatusEnum::PREPARING->value);
            $this->cache->setProgress($jobId, 0);

            $this->addAssignmentsToZip($zip, $jobId, $items, $ip, $userAgent, $tmpFiles);

            $this->finalizeZip($zip, $tmpFiles, $jobId, $tmpPath, $downloadName);
        } catch (\Throwable $e) {
            $this->cache->setStatus($jobId, DownloadStatusEnum::FAILED->value);
            // zip->close() must be called even on failure or libzip leaks file handles
            try {
                $zip->close();
            } catch (\Throwable) {
            }
            Storage::delete($tmpPath);
            throw $e;
        } finally {
            // always wipe any Dropbox tmp copies regardless of success or failure
            foreach ($tmpFiles as $file) {
                Storage::delete($file);
            }
        }

        return $tmpPath;
    }

    private function jobId(Batch $batch, Channel $channel): string
    {
        return $batch->getKey() . '_' . $channel->getKey();
    }

    private function downloadName(?Batch $batch, Channel $channel): string
    {
        $id = $batch ? $batch->getKey() : Str::uuid();

        return sprintf(
            'videos_%s_%s_selected.zip',
            $id,
            Str::slug((string)$channel->getAttribute('name')),
        );
    }

    private function zipPath(string $jobId): string
    {
        return "zips/{$jobId}.zip";
    }

    private function prepareDirectories(): void
    {
        Storage::makeDirectory('zips');
        Storage::makeDirectory('zips/tmp');
    }

    /**
     * @param Collection<Assignment> $items
     */
    private function createZipArchive(string $tmpPath, Collection $items): ZipArchive
    {
        $zip = new ZipArchive();
        if ($zip->open(Storage::path($tmpPath), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new ZipBuildException('Cannot create the ZIP archive.');
        }
        if (!$zip->addFromString('info.csv', $this->csvService->buildInfoCsv($items))) {
            throw new ZipBuildException('Cannot add metadata to the ZIP archive.');
        }

        return $zip;
    }

    /**
     * @param Collection<Assignment> $items
     * @param array<int, string> $tmpFiles Temporary files to clean up even if processing fails.
     */
    private function addAssignmentsToZip(
        ZipArchive $zip,
        string $jobId,
        Collection $items,
        string $ip,
        ?string $userAgent,
        array &$tmpFiles
    ): void {
        $total = max($items->count(), 1);
        $processed = 0;

        foreach ($items as $assignment) {
            $this->processAssignment($zip, $jobId, $assignment, $tmpFiles, $processed, $total);

            $processed++;
            $this->updateProgress($jobId, $processed, $total);
        }

    }

    /**
     * @param array<int, string> $tmpFiles
     * @param int $processed Number of assignments already packed, used for progress reporting.
     * @param int $total Number of assignments in this archive.
     */
    private function processAssignment(
        ZipArchive $zip,
        string $jobId,
        Assignment $assignment,
        array &$tmpFiles,
        int $processed,
        int $total
    ): void {
        /** @var Video $video */
        $video = $assignment->video;
        if ($video === null) {
            throw new ZipBuildException('An offered video is no longer available.');
        }
        $disk = $video->getDisk();
        $path = $video->getAttribute('path');

        if (!$disk->exists($path)) {
            Log::channel('single')->warning('remote path not exists', [
                'path' => $path,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
            ]);
            throw new ZipBuildException('An offered video file is missing.');
        }

        $nameInZip = $this->sanitizeName($video);
        while ($zip->locateName($nameInZip) !== false) {
            $nameInZip = $assignment->getKey() . '_' . $nameInZip;
        }
        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::QUEUED->value);
        $localPath = $this->localVideoPath($video, $disk->path($path), $jobId, $nameInZip, $tmpFiles, $processed, $total);

        if ($localPath === null) {
            Log::channel('single')->warning('local path broken', [
                'localPath' => $localPath,
                'nameInZip' => $nameInZip,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
            ]);
            throw new ZipBuildException('An offered video could not be read.');
        }

        $this->cache->setStatus($jobId, DownloadStatusEnum::PACKING->value);
        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::PACKING->value);
        $isOk = $zip->addFile($localPath, $nameInZip);
        if (!$isOk) {
            Log::channel('single')->warning('ZIP add failed', [
                'localPath' => $localPath,
                'nameInZip' => $nameInZip,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
                'exists' => file_exists($localPath),
            ]);
            throw new ZipBuildException('An offered video could not be added to the ZIP archive.');
        }

        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::READY->value);
    }

    /**
     * Resolve a readable local file, copying remote videos in chunks so progress keeps moving.
     * @param array<int, string> $tmpFiles
     */
    private function localVideoPath(
        Video $video,
        string $localDiskPath,
        string $jobId,
        string $nameInZip,
        array &$tmpFiles,
        int $processed,
        int $total
    ): ?string {
        if ($video->getAttribute('disk') !== 'dropbox') {
            $this->cache->setStatus($jobId, DownloadStatusEnum::DOWNLOADED->value);
            $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::DOWNLOADED->value);
            return $localDiskPath;
        }

        $this->cache->setStatus($jobId, DownloadStatusEnum::DOWNLOADING->value);
        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::DOWNLOADING->value);
        /**
         * @var DropboxAdapter $disk
         */
        $disk = $video->getDisk();
        $path = $video->getAttribute('path');
        $stream = $disk->readStream($path);

        if (!is_resource($stream)) {
            Log::channel('single')->warning('Dropbox readStream failed', [
                'path' => $video->getAttribute('path'),
            ]);
            return null;
        }

        $tmpFile = 'zips/tmp/' . Str::uuid()->toString();
        $tmpFiles[] = $tmpFile;
        $localPath = Storage::path($tmpFile);

        try {
            $localHandle = fopen($localPath, 'w+b');

            if ($localHandle === false) {
                Log::channel('single')->warning('local handler failed', [
                    'localPath' => $localPath,
                    'video_id' => $video->getKey(),
                    'disk' => $video->getAttribute('disk'),
                    'exists' => file_exists($localPath),
                ]);
                return null;
            }

            try {
                $size = (int) $disk->size($path);
                $bytes = 0;
                while (($copied = stream_copy_to_stream($stream, $localHandle, self::TRANSFER_CHUNK_BYTES)) > 0) {
                    $bytes += $copied;
                    $this->updateProgress($jobId, $processed, $total, $size > 0 ? min($bytes / $size, 1.0) : 0.0);
                }
                if ($copied === false || $bytes !== $size) {
                    throw new ZipBuildException('The remote video transfer was incomplete.');
                }
            } finally {
                fclose($localHandle);
            }
        } finally {
            // always release the Guzzle/HTTP stream so its /tmp backing file is freed
            fclose($stream);
        }

        $this->cache->setStatus($jobId, DownloadStatusEnum::DOWNLOADED->value);
        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::DOWNLOADED->value);

        return $localPath;
    }

    private function sanitizeName(Video $video): string
    {
        $name = $video->getAttribute('original_name') ?: basename($video->getAttribute('path'));

        return preg_replace('/[\\\\\/:*?"<>|]+/', '_', $name);
    }

    /**
     * @param float $currentFileShare Transferred share of the file currently being processed, from 0 to 1.
     */
    private function updateProgress(string $jobId, int $processed, int $total, float $currentFileShare = 0.0): void
    {
        $pct = (int)floor(($processed + $currentFileShare) * 100 / max($total, 1));
        $this->cache->setProgress($jobId, $pct);
    }

    /**
     * @param array<int, string> $tmpFiles
     */
    private function finalizeZip(
        ZipArchive $zip,
        array $tmpFiles,
        string $jobId,
        string $tmpPath,
        string $downloadName
    ): void {
        $this->cache->setStatus($jobId, DownloadStatusEnum::PACKING->value);
        if (!$zip->close()) {
            throw new ZipBuildException('The ZIP archive could not be finalized.');
        }

        foreach ($tmpFiles as $file) {
            Storage::delete($file);
        }

        $this->cache->setFile($jobId, $tmpPath);
        $this->cache->setName($jobId, $downloadName);
        $this->cache->setProgress($jobId, 100);
        $this->cache->setStatus($jobId, DownloadStatusEnum::READY->value);
    }

}
