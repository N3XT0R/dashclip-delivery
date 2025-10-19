<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Enum\BatchTypeEnum;
use App\Facades\DynamicStorage;
use App\Services\BatchService;
use App\Services\CsvService;
use App\Services\VideoService;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Filesystem\Filesystem;
use Laravel\Reverb\Loggers\Log;

class IngestScanner
{
    private const ALLOWED_EXTENSIONS = ['mp4', 'mov', 'mkv', 'avi', 'm4v', 'webm'];

    private const CHUNK_SIZE = 8 * 1024 * 1024; // 8 MB
    private const CSV_REGEX = '/\.(csv|txt)$/i';

    private ?OutputStyle $output = null;

    public function __construct(
        private BatchService $batchService,
        private CsvService $csvService,
        private VideoService $videoService
    ) {
    }

    public function setOutput(?OutputStyle $outputStyle = null): void
    {
        $this->output = $outputStyle;
    }

    private function log(string $message): void
    {
        $this->output?->writeln($message);
    }

    public function scanDisk(string $inboxPath, string $targetDiskName): array
    {
        $inboxDisk = DynamicStorage::fromPath($inboxPath);
        $this->log(sprintf('Starte Scan: %s -> %s', $inboxPath, $targetDiskName));
        $batch = $this->batchService->createNewBatch(BatchTypeEnum::INGEST);
        $stats = ['new' => 0, 'dups' => 0, 'err' => 0];

        // CSV-Import für alle Verzeichnisse
        $this->importCsvForDirectory($inboxDisk);

        // Videodateien verarbeiten
        $allFiles = DynamicStorage::listFiles($inboxDisk);
        foreach ($allFiles as $file) {
            if (!$file->isOneOfExtensions(self::ALLOWED_EXTENSIONS)) {
                continue;
            }
            $this->log("Verarbeite {$file}");
            try {
            } catch (\Throwable $e) {
            }
        }
    }

    private function importCsvForDirectory(Filesystem $inboxDisk): void
    {
        foreach ($inboxDisk->allDirectories() as $directory) {
            try {
                $this->csvService->importCsvForDisk($inboxDisk, $directory);
            } catch (\Throwable $e) {
                Log::warning('CSV-Import fehlgeschlagen', [
                    'dir' => $directory,
                    'e' => $e->getMessage(),
                ]);
                $this->log("Warnung: CSV-Import für {$directory} fehlgeschlagen ({$e->getMessage()})");
            }
        }
    }
}