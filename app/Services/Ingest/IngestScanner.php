<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\DTO\FileInfoDto;
use App\Enum\BatchTypeEnum;
use App\Facades\DynamicStorage;
use App\Services\BatchService;
use App\Services\InfoImporter;
use App\Services\VideoService;
use Illuminate\Console\OutputStyle;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

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
        $this->assertDirectory($inboxPath);
        $inboxDisk = DynamicStorage::fromPath($inboxPath);
        $this->log(sprintf('Starte Scan: %s -> %s', $inboxPath, $targetDiskName));
        $batch = $this->batchService->createNewBatch(BatchTypeEnum::INGEST);
        $stats = ['new' => 0, 'dups' => 0, 'err' => 0];

        $allFiles = $this->listFiles($inboxDisk);
        foreach ($allFiles as $relativePath) {
        }
    }

    private function listFiles(Filesystem $disk, string $basePath = ''): Collection
    {
        return collect($disk->allFiles($basePath))
            ->map(fn(string $path) => FileInfoDto::fromPath($path));
    }
}