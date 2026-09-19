<?php

declare(strict_types=1);

namespace App\Services\Zip;

use App\Enum\DownloadStatusEnum;
use App\Exceptions\Zip\ZipBuildException;
use App\Exceptions\Zip\ZipEmptyException;
use App\Exceptions\Zip\ZipEntrySkippedException;
use App\Models\{Assignment, Batch, Channel, Video};
use App\Services\CsvService;
use App\Services\DownloadCacheService;
use Illuminate\Support\Collection;
use League\Flysystem\FilesystemException;
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

        $zip = $this->createZipArchive($tmpPath);
        $tmpFiles = [];

        try {
            $this->cache->setStatus($jobId, DownloadStatusEnum::PREPARING->value);
            $this->cache->setProgress($jobId, 0);

            $packed = $this->addAssignmentsToZip($zip, $jobId, $channel, $items, $tmpFiles);
            if ($packed->isEmpty()) {
                throw ZipEmptyException::allSkipped();
            }
            if (!$zip->addFromString('info.csv', $this->csvService->buildInfoCsv($packed))) {
                throw new ZipBuildException('Cannot add metadata to the ZIP archive.');
            }
            // only packed offers are marked as downloaded once the archive is fetched
            $this->cache->setAssignments($jobId, $packed->pluck('id')->all());

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

    private function createZipArchive(string $tmpPath): ZipArchive
    {
        $zip = new ZipArchive();
        if ($zip->open(Storage::path($tmpPath), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new ZipBuildException('Cannot create the ZIP archive.');
        }

        return $zip;
    }

    /**
     * Pack every readable video and skip the others, so one broken video never blocks the rest.
     * @param Collection<Assignment> $items
     * @param array<int, string> $tmpFiles Temporary files to clean up even if processing fails.
     * @return Collection<int, Assignment> The assignments whose videos are in the archive.
     */
    private function addAssignmentsToZip(
        ZipArchive $zip,
        string $jobId,
        Channel $channel,
        Collection $items,
        array &$tmpFiles
    ): Collection {
        $total = max($items->count(), 1);
        $processed = 0;
        $packed = collect();

        foreach ($items as $assignment) {
            $nameInZip = $this->entryName($zip, $assignment);
            try {
                $this->processAssignment($zip, $jobId, $assignment, $nameInZip, $tmpFiles, $processed, $total);
                $packed->push($assignment);
            } catch (ZipEntrySkippedException|FilesystemException $exception) {
                $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::SKIPPED->value);
                Log::warning('Offered video skipped in ZIP download', [
                    'reason' => $exception instanceof ZipEntrySkippedException ? $exception->reason : 'storage_error',
                    'message' => $exception->getMessage(),
                    'job_id' => $jobId,
                    'assignment_id' => $assignment->getKey(),
                    'video_id' => $assignment->getAttribute('video_id'),
                    'channel_id' => $channel->getKey(),
                    'file' => $nameInZip,
                ]);
            }

            $processed++;
            $this->updateProgress($jobId, $processed, $total);
        }

        return $packed;
    }

    /** Name the archive entry after the video and keep it unique inside the archive. */
    private function entryName(ZipArchive $zip, Assignment $assignment): string
    {
        $video = $assignment->video;
        $nameInZip = $video !== null ? $this->sanitizeName($video) : 'offer_'.$assignment->getKey();
        while ($zip->locateName($nameInZip) !== false) {
            $nameInZip = $assignment->getKey() . '_' . $nameInZip;
        }

        return $nameInZip;
    }

    /**
     * @param array<int, string> $tmpFiles
     * @throws ZipEntrySkippedException When this video cannot be packed.
     * @param int $processed Number of assignments already packed, used for progress reporting.
     * @param int $total Number of assignments in this archive.
     */
    private function processAssignment(
        ZipArchive $zip,
        string $jobId,
        Assignment $assignment,
        string $nameInZip,
        array &$tmpFiles,
        int $processed,
        int $total
    ): void {
        /** @var Video|null $video */
        $video = $assignment->video;
        if ($video === null) {
            throw ZipEntrySkippedException::videoDeleted();
        }
        $disk = $video->getDisk();
        $path = $video->getAttribute('path');

        if (!$disk->exists($path)) {
            Log::warning('remote path not exists', [
                'path' => $path,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
            ]);
            throw ZipEntrySkippedException::fileMissing();
        }

        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::QUEUED->value);
        $localPath = $this->localVideoPath($video, $disk->path($path), $jobId, $nameInZip, $tmpFiles, $processed, $total);

        if ($localPath === null) {
            Log::warning('local path broken', [
                'localPath' => $localPath,
                'nameInZip' => $nameInZip,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
            ]);
            throw ZipEntrySkippedException::unreadable();
        }

        $this->cache->setStatus($jobId, DownloadStatusEnum::PACKING->value);
        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::PACKING->value);
        $isOk = $zip->addFile($localPath, $nameInZip);
        if (!$isOk) {
            Log::warning('ZIP add failed', [
                'localPath' => $localPath,
                'nameInZip' => $nameInZip,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
                'exists' => file_exists($localPath),
            ]);
            throw ZipEntrySkippedException::notAdded();
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
            Log::warning('Dropbox readStream failed', [
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
                Log::warning('local handler failed', [
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
                    throw ZipEntrySkippedException::incompleteTransfer();
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
