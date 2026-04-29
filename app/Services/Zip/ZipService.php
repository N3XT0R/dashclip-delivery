<?php

declare(strict_types=1);

namespace App\Services\Zip;

use App\Enum\DownloadStatusEnum;
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
        if ($batch) {
            $jobId = $this->jobId($batch, $channel);
        }

        $downloadName = $this->downloadName($batch, $channel);
        $tmpPath = $this->zipPath($jobId);

        $this->prepareDirectories();

        // remember assignments for later download tracking
        $this->cache->setAssignments($jobId, $items->pluck('id')->all());

        $zip = $this->createZipArchive($tmpPath, $items);
        $tmpFiles = [];

        try {
            $this->cache->setStatus($jobId, DownloadStatusEnum::PREPARING->value);
            $this->cache->setProgress($jobId, 0);

            $tmpFiles = $this->addAssignmentsToZip($zip, $jobId, $items, $ip, $userAgent);

            $this->finalizeZip($zip, $tmpFiles, $jobId, $tmpPath, $downloadName);
        } catch (\Throwable $e) {
            // zip->close() must be called even on failure or libzip leaks file handles
            try {
                $zip->close();
            } catch (\Throwable) {
            }
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
        $zip->open(Storage::path($tmpPath), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('info.csv', $this->csvService->buildInfoCsv($items));

        return $zip;
    }

    /**
     * @param Collection<Assignment> $items
     * @return array<int, string>  temporary files created during download
     */
    private function addAssignmentsToZip(
        ZipArchive $zip,
        string $jobId,
        Collection $items,
        string $ip,
        ?string $userAgent
    ): array {
        $tmpFiles = [];
        $total = max($items->count(), 1);
        $processed = 0;

        foreach ($items as $assignment) {
            $this->processAssignment($zip, $jobId, $assignment, $ip, $userAgent, $tmpFiles);

            $processed++;
            $this->updateProgress($jobId, $processed, $total);
        }

        return $tmpFiles;
    }

    /**
     * @param array<int, string> $tmpFiles
     */
    private function processAssignment(
        ZipArchive $zip,
        string $jobId,
        Assignment $assignment,
        string $ip,
        ?string $userAgent,
        array &$tmpFiles
    ): void {
        /** @var Video $video */
        $video = $assignment->video;
        $disk = $video->getDisk();
        $path = $video->getAttribute('path');

        if (!$disk->exists($path)) {
            Log::channel('single')->warning('remote path not exists', [
                'path' => $path,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
            ]);
            return;
        }

        $nameInZip = $this->sanitizeName($video);
        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::QUEUED->value);
        $localPath = $this->localVideoPath($video, $disk->path($path), $jobId, $nameInZip, $tmpFiles);

        if ($localPath === null) {
            Log::channel('single')->warning('local path broken', [
                'localPath' => $localPath,
                'nameInZip' => $nameInZip,
                'video_id' => $video->getKey(),
                'disk' => $video->getAttribute('disk'),
            ]);
            return;
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
        }

        $this->cache->setFileStatus($jobId, $nameInZip, DownloadStatusEnum::READY->value);
    }

    /**
     * @param array<int, string> $tmpFiles
     */
    private function localVideoPath(
        Video $video,
        string $localDiskPath,
        string $jobId,
        string $nameInZip,
        array &$tmpFiles
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
                stream_copy_to_stream($stream, $localHandle);
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

    private function updateProgress(string $jobId, int $processed, int $total): void
    {
        $pct = (int)floor($processed * 100 / max($total, 1));
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
        $zip->close();

        foreach ($tmpFiles as $file) {
            Storage::delete($file);
        }

        $this->cache->setFile($jobId, $tmpPath);
        $this->cache->setName($jobId, $downloadName);
        $this->cache->setProgress($jobId, 100);
        $this->cache->setStatus($jobId, DownloadStatusEnum::READY->value);
    }

}
