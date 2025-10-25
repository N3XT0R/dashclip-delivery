<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\BatchTypeEnum;
use App\Models\Batch;
use App\Services\Dropbox\AutoRefreshTokenProvider;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Dropbox\Client as DropboxClient;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

/**
 * Recursively scans an input directory and imports new video files
 * into the configured storage. Detects duplicates using SHA-256.
 * @deprecated use \App\Services\Ingest\IngestScanner instead
 */
final class IngestScanner
{
    /** @var string[] */
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

    /**
     * Scan an inbox recursively and ingest new videos.
     *
     * @return array{new:int, dups:int, err:int}
     */
    public function scan(string $inbox, string $diskName): array
    {
        $this->assertDirectory($inbox);

        $this->log(sprintf('Starte Scan: %s -> %s', $inbox, $diskName));

        $batch = $this->batchService->createNewBatch(BatchTypeEnum::INGEST);

        $stats = ['new' => 0, 'dups' => 0, 'err' => 0];

        $iterator = $this->makeRecursiveIterator($inbox);

        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $path => $fileInfo) {
            // 1) If the entry is a directory: optionally import CSV from subfolder
            if ($fileInfo->isDir()) {
                $this->maybeImportCsvForDirectory($fileInfo->getPathname());
                continue; // directory logic only; files are handled separately
            }

            // 2) Process only valid video files with allowed extensions
            if (!$fileInfo->isFile() || !$this->isAllowedExtension($fileInfo)) {
                continue;
            }

            $this->log("Verarbeite {$path}");

            try {
                $result = $this->processFile(
                    path: $path,
                    ext: strtolower($fileInfo->getExtension()),
                    fileName: $fileInfo->getFilename(),
                    diskName: $diskName
                );

                $stats[$result]++;

                $this->updateBatchStats($batch, $stats, $diskName);
            } catch (Throwable $e) {
                Log::error($e->getMessage(), ['file' => $path]);
                $this->log("Fehler: {$e->getMessage()}");
                $stats['err']++;
            }
        }

        $batch->update([
            'finished_at' => now(),
            'stats' => $stats + ['disk' => $diskName],
        ]);

        $this->log(sprintf('Fertig. Neu: %d  Doppelt: %d  Fehler: %d', $stats['new'], $stats['dups'], $stats['err']));

        return $stats;
    }

    // ─────────────────────────────────────────────────────────────────────────────

    private function isAllowedExtension(\SplFileInfo $file): bool
    {
        return in_array(strtolower($file->getExtension()), self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * @param  string  $path
     * @param  string  $ext
     * @param  string  $fileName
     * @param  string  $diskName
     * @return 'new'|'dups'|'err'
     */
    public function processFile(string $path, string $ext, string $fileName, string $diskName): string
    {
        //"/var/www/html/storage/app/private/uploads/tmp/01K7XY7J75282QZV2Q4DA2REJ0.mp4" -> path
        $hash = hash_file('sha256', $path);
        $bytes = filesize($path);
        $videoService = $this->videoService;

        if ($videoService->isDuplicate($hash)) {
            @unlink($path);
            $this->log('Duplikat übersprungen');
            return 'dups';
        }

        $dstRel = $this->buildDestinationPath($hash, $ext);

        // Create video before upload so preview can be generated from local path
        $video = $videoService->createLocal($hash, $ext, $bytes, $path, $fileName);

        // Re-import clip information after video has been created
        $this->maybeImportCsvForDirectory(dirname($path));
        $video->refresh();

        $previewUrl = $videoService->generatePreview($video, $path, $this->output, fn($msg) => $this->log($msg));

        $this->log("Upload nach {$dstRel}");
        $uploaded = $this->uploadFile($path, $dstRel, $diskName, $bytes);

        if (!$uploaded) {
            $video->delete();
            $this->log('Upload fehlgeschlagen');
            return 'err';
        }

        $videoService->finalizeUpload($video, $dstRel, $diskName, $previewUrl);
        $this->log('Upload abgeschlossen');
        return 'new';
    }

    private function buildDestinationPath(string $hash, string $ext): string
    {
        $sub = substr($hash, 0, 2).'/'.substr($hash, 2, 2);
        return sprintf('videos/%s/%s%s', $sub, $hash, $ext !== '' ? ".{$ext}" : '');
    }

    /**
     * @param  string  $srcPath
     * @param  string  $dstRel
     * @param  string  $diskName
     * @param  int  $bytes
     * @return bool
     * @deprecated use UploadService::uploadFile instead
     */
    private function uploadFile(string $srcPath, string $dstRel, string $diskName, int $bytes): bool
    {
        $read = fopen($srcPath, 'rb');
        if ($read === false) {
            throw new RuntimeException("Konnte Quelle nicht öffnen: {$srcPath}");
        }

        $bar = $this->createProgressBar($bytes);

        try {
            if ($diskName === 'dropbox') {
                $this->uploadToDropbox($read, $dstRel, $bytes, $bar);
                @unlink($srcPath);
                $bar?->finish();
                return true;
            }

            $disk = Storage::disk($diskName);
            $dest = $disk->path($dstRel);

            $this->ensureDirectory(dirname($dest));

            $write = fopen($dest, 'wb');
            if ($write === false) {
                throw new RuntimeException("Konnte Ziel nicht öffnen: {$dest}");
            }

            try {
                while (!feof($read)) {
                    $chunk = fread($read, self::CHUNK_SIZE);
                    if ($chunk === false) {
                        break;
                    }
                    fwrite($write, $chunk);
                    $bar?->advance(strlen($chunk));
                }
            } finally {
                fclose($write);
            }

            @unlink($srcPath);
            $bar?->finish();

            return true;
        } finally {
            // Safe close in case exceptions are thrown above
            if (is_resource($read)) {
                fclose($read);
            }
        }

        return false;
    }

    /**
     * @param $read
     * @param  string  $dstRel
     * @param  int  $bytes
     * @param  ProgressBar|null  $bar
     * @return void
     * @deprecated use DropboxUploadService::uploadFile instead
     */
    private function uploadToDropbox($read, string $dstRel, int $bytes, ?ProgressBar $bar = null): void
    {
        $root = (string)config('filesystems.disks.dropbox.root', '');
        $targetPath = '/'.trim($root.'/'.$dstRel, '/');

        /** @var AutoRefreshTokenProvider $provider */
        $provider = app(AutoRefreshTokenProvider::class);
        $client = new DropboxClient($provider);

        // Edge-Case: empty file
        if ($bytes === 0) {
            $client->upload($targetPath, '');
            return;
        }

        $firstChunk = fread($read, self::CHUNK_SIZE) ?: '';
        $cursor = $client->uploadSessionStart($firstChunk);
        $bar?->advance(strlen($firstChunk));

        $transferred = strlen($firstChunk);

        while (!feof($read)) {
            $chunk = fread($read, self::CHUNK_SIZE) ?: '';
            $transferred += strlen($chunk);

            if ($transferred >= $bytes) {
                // last Chunk
                $client->uploadSessionFinish($chunk, $cursor, $targetPath);
            } else {
                $cursor = $client->uploadSessionAppend($chunk, $cursor);
            }

            $bar?->advance(strlen($chunk));
        }
    }

    /**
     * @param  string  $dirPath
     * @return void
     * @deprecated use CsvService::importFromDirectory instead
     */
    private function maybeImportCsvForDirectory(string $dirPath): void
    {
        $csvFiles = $this->findCsvFiles($dirPath);
        foreach ($csvFiles as $csv) {
            try {
                $result = $this->infoImporter->import($csv);
                if (($result['warnings'] ?? 0) === 0) {
                    @unlink($csv);
                }
            } catch (Throwable $e) {
                Log::warning('info:import fehlgeschlagen', [
                    'dir' => $dirPath,
                    'csv' => $csv,
                    'e' => $e->getMessage(),
                ]);
                $this->log("Warnung: info:import für {$dirPath} fehlgeschlagen ({$e->getMessage()})");
            }
        }
    }

    /**
     * @return string[]
     * @deprecated no replacement
     */
    private function findCsvFiles(string $dirPath): array
    {
        $files = [];
        try {
            foreach (new \DirectoryIterator($dirPath) as $f) {
                if ($f->isFile() && preg_match(self::CSV_REGEX, $f->getFilename())) {
                    $files[] = $f->getPathname();
                }
            }
        } catch (\UnexpectedValueException) {
            // Not readable -> treat as "no CSV"
        }

        return $files;
    }

    private function makeRecursiveIterator(string $baseDir): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $baseDir,
                \FilesystemIterator::SKIP_DOTS
                | \FilesystemIterator::CURRENT_AS_FILEINFO
                | \FilesystemIterator::FOLLOW_SYMLINKS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    private function updateBatchStats(Batch $batch, array $stats, string $diskName): void
    {
        $batch->update([
            'stats' => $stats + ['disk' => $diskName],
        ]);
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Konnte Zielordner nicht erstellen: {$dir}");
        }
    }

    private function assertDirectory(string $path): void
    {
        if (!is_dir($path)) {
            throw new RuntimeException("Inbox fehlt: {$path}");
        }
    }

    private function createProgressBar(int $max): ?ProgressBar
    {
        if ($this->output === null) {
            return null;
        }

        $bar = $this->output->createProgressBar($max);
        $bar->start();

        return $bar;
    }

    private function log(string $message): void
    {
        $this->output?->writeln($message);
    }
}
