<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\DTO\FileInfoDto;
use App\Enum\BatchTypeEnum;
use App\Enum\Ingest\IngestResult;
use App\Exceptions\InvalidTimeRangeException;
use App\Exceptions\PreviewGenerationException;
use App\Facades\DynamicStorage;
use App\Facades\PathBuilder;
use App\Models\User;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;
use App\Services\BatchService;
use App\Services\CsvService;
use App\Services\PreviewService;
use App\Services\Upload\UploadService;
use App\Services\VideoService;
use App\ValueObjects\ClipImportResult;
use App\ValueObjects\IngestStats;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class IngestScanner
{
    private const ALLOWED_EXTENSIONS = ['mp4', 'mov', 'mkv', 'avi', 'm4v', 'webm'];


    private ?OutputStyle $output = null;

    public function __construct(
        private readonly BatchService $batchService,
        private readonly CsvService $csvService,
        private readonly VideoService $videoService
    ) {
    }

    public function setOutput(?OutputStyle $outputStyle = null): void
    {
        $this->output = $outputStyle;
    }

    private function log(string $message, string $level = 'info', array $context = []): void
    {
        $this->output?->writeln($message);
        Log::log($level, $message, $context);
    }

    public function scanDisk(string $inboxPath, string $targetDiskName): IngestStats
    {
        $inboxDisk = DynamicStorage::fromPath($inboxPath);
        $this->log(sprintf('Starte Scan: %s -> %s', $inboxPath, $targetDiskName));
        $batch = $this->batchService->createNewBatch(BatchTypeEnum::INGEST);
        $stats = new IngestStats();

        // CSV-Import für alle Verzeichnisse
        $this->importCsvForDirectory($inboxDisk);

        // Videodateien verarbeiten
        $allFiles = DynamicStorage::listFiles($inboxDisk);
        foreach ($allFiles as $file) {
            if (false === $file->isOneOfExtensions(self::ALLOWED_EXTENSIONS)) {
                continue;
            }

            $this->log("Verarbeite {$file->basename}");

            try {
                $result = $this->processFile($inboxDisk, $file, $targetDiskName);
                $stats->increment($result);
            } catch (Throwable $e) {
                $stats->increment(IngestResult::ERR);
                $this->log("Fehler bei der Verarbeitung: {$e->getMessage()}", 'error',
                    ['exception' => $e, 'file' => $file->path]);
            }
            $this->batchService->updateStats($batch, $stats);
        }

        $this->batchService->finalizeStats($batch, $stats);
        $this->log(sprintf('Fertig. %s', (string)$stats));

        return $stats;
    }

    /**
     * Import CSV files for all directories in the given disk.
     * @param  Filesystem  $inboxDisk
     * @param  bool  $deleteAfter
     * @return ClipImportResult
     */
    private function importCsvForDirectory(Filesystem $inboxDisk, bool $deleteAfter = false): ClipImportResult
    {
        $aggregate = ClipImportResult::empty();

        // Include root ("") + all subdirectories
        foreach (array_merge([''], $inboxDisk->allDirectories()) as $dir) {
            try {
                if ($res = $this->csvService->importCsvForDisk($inboxDisk, $dir, $deleteAfter)) {
                    $aggregate->merge($res);
                }
            } catch (Throwable $e) {
                // Log warning and continue on failure
                $this->log(
                    "CSV import failed for {$dir} ({$e->getMessage()})",
                    'warning',
                    ['exception' => $e, 'dir' => $dir]
                );
            }
        }

        return $aggregate;
    }


    /**
     * Process a single file from the inbox-disk.
     * @param  Filesystem  $inboxDisk
     * @param  FileInfoDto  $file
     * @param  string  $diskName
     * @param  User|null  $user
     * @param  string|null  $inboxDiskName
     * @return IngestResult
     * @throws Throwable
     */
    public function processFile(
        Filesystem $inboxDisk,
        FileInfoDto $file,
        string $diskName,
        ?User $user = null,
        ?string $inboxDiskName = null
    ): IngestResult {
        $hash = DynamicStorage::getHashForFileInfoDto($inboxDisk, $file);
        $pathToFile = $file->path;
        $ext = $file->extension;
        $videoService = $this->videoService;
        $previewService = app(PreviewService::class);
        $previewService->setOutput($this->output);
        $uploadService = app(UploadService::class);

        if ($videoService->isDuplicate($hash)) {
            $inboxDisk->delete($pathToFile);
            $this->log('Duplikat übersprungen', 'info', [
                'file' => $file->path,
                'hash' => $hash,
                'path_to_file' => $pathToFile,
            ]);

            if ($user) {
                $this->notifyUserUploadIsDuplicate($user, $file);
            }
            return IngestResult::DUPS;
        }

        $dstRel = PathBuilder::forVideo($hash, $ext);


        try {
            DB::beginTransaction();
            $video = $videoService->createVideoBydDiskAndFileInfoDto(
                $inboxDiskName ?: 'dynamicStorage',
                $inboxDisk,
                $file);
            $importResult = $this->importCsvForDirectory($inboxDisk, true);
            $video->refresh();

            $clip = $importResult->clipsForVideo($video)->first();

            $startSec = $clip?->start_sec ?? 0;
            $endSec = $clip?->end_sec ?? null;
            DB::commit();

            $previewUrl = $previewService->generatePreviewByDisk(
                $inboxDisk,
                $pathToFile,
                (int)$video->getKey(),
                $startSec,
                $endSec
            );

            $uploadService->uploadFile($inboxDisk, $pathToFile, $diskName, $dstRel);
            $videoService->finalizeUpload($video, $dstRel, $diskName, $previewUrl);

            $this->log('Upload abgeschlossen für '.$file->basename, 'info', [
                'path' => $video->path,
                'disk' => $video->disk,
                'original_file' => $file->path,
                'video_id' => $video->getKey(),
            ]);
            if ($user) {
                $this->notifyUserUploadComplete($user, $file);
            }
        } catch (PreviewGenerationException|InvalidTimeRangeException $e) {
            $this->log($e->getMessage(), 'error', $e->context());
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->log(
                'Upload fehlgeschlagen: '.$e->getMessage(),
                'error',
                [
                    'exception' => $e,
                    'file' => $file->path,
                ]
            );

            return IngestResult::ERR;
        }


        return IngestResult::NEW;
    }


    private function notifyUserUploadComplete(User $user, FileInfoDto $file): void
    {
        $user->notify(new UserUploadProceedNotification(
            filename: $file->originalName ?? $file->basename,
            note: 'Alles erfolgreich abgeschlossen.'
        ));
    }

    private function notifyUserUploadIsDuplicate(User $user, FileInfoDto $file): void
    {
        $user->notify(new UserUploadDuplicatedNotification(
            filename: $file->originalName ?? $file->basename,
            note: 'Die Datei wurde als Duplikat erkannt und nicht erneut hochgeladen.'
        ));
    }

}