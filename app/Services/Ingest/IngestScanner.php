<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\Enum\BatchTypeEnum;
use App\Facades\DynamicStorage;
use App\Services\BatchService;
use App\Services\InfoImporter;
use App\Services\VideoService;
use Illuminate\Console\OutputStyle;

class IngestScanner
{
    private const ALLOWED_EXTENSIONS = ['mp4', 'mov', 'mkv', 'avi', 'm4v', 'webm'];

    private const CHUNK_SIZE = 8 * 1024 * 1024; // 8 MB
    private const CSV_REGEX = '/\.(csv|txt)$/i';

    private ?OutputStyle $output = null;

    public function __construct(
        private BatchService $batchService,
        private InfoImporter $infoImporter,
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

        $allFiles = DynamicStorage::listFiles($inboxDisk);
        foreach ($allFiles as $relativePath) {
        }
    }
}