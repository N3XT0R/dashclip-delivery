<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\ValueObjects\ClipImportResult;
use App\Services\InfoImporter;
use Illuminate\Console\Command;

/**
 * Command to import clip metadata from a CSV/TXT file.
 * Usage:
 *  php artisan info:import --csv=/path/to/file.csv
 *  php artisan info:import --dir=/path/to/directory
 * Options:
 * --infer-role=1 : Infer role (F/R) from filename suffix _F/_R if the column is empty
 * --default-bundle= : Fallback bundle if the CSV field is empty
 * --default-submitter= : Fallback for submitted_by if the CSV field is empty
 * --keep-csv=1 : Keep the CSV/TXT file after import (1 = do not delete)
 * @todo refactor to service class at version 4.0
 */
class InfoImport extends Command
{
    protected $signature = 'info:import
        {--dir= : Upload directory containing clips (scan recursively for CSV/TXT files)}
        {--csv= : Optional: direct path to a CSV/TXT file}
        {--infer-role=1 : Infer role (F/R) from filename suffix _F/_R if the column is empty}
        {--default-bundle= : Fallback bundle if the CSV field is empty}
        {--default-submitter= : Fallback for submitted_by if the CSV field is empty}
        {--keep-csv=1 : Keep the CSV/TXT file after import (1 = do not delete)}';

    protected $description = 'Imports clip metadata (start/end/note/bundle/role/submitted_by) from a CSV file.';

    public function handle(InfoImporter $importer): int
    {
        $csvPath = $this->resolveCsvPath();
        if ($csvPath === null) {
            return self::FAILURE;
        }

        try {
            $result = $importer->import(
                $csvPath,
                [
                    'infer-role' => $this->optionTruthy('infer-role'),
                    'default-bundle' => (string)$this->option('default-bundle'),
                    'default-submitter' => (string)$this->option('default-submitter'),
                ],
                fn ($msg) => $this->warn($msg)
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->handleCsvAfterImport($csvPath);
        $this->reportResult($result);

        return self::SUCCESS;
    }

    /**
     * The file to import, taken from --csv or looked up in --dir.
     * @return string|null null when there is nothing to import, the reason is reported by then
     */
    private function resolveCsvPath(): ?string
    {
        $csvPath = (string)($this->option('csv') ?? '');
        $dir = (string)($this->option('dir') ?? '');

        if ($csvPath === '' && $dir === '') {
            $this->error('Gib entweder --dir=/pfad/zum/ordner ODER --csv=/pfad/zur/datei.csv an.');
            return null;
        }

        if ($csvPath === '') {
            $csvPath = (string)$this->findSingleCsvInDirectory($dir);
            if ($csvPath === '') {
                return null;
            }
        }

        if (!is_file($csvPath)) {
            $this->error("CSV nicht gefunden: {$csvPath}");
            return null;
        }

        return $csvPath;
    }

    /**
     * The one CSV or TXT file of a directory tree.
     * @param string $dir
     * @return string|null null when the directory is unusable or holds none or several files
     */
    private function findSingleCsvInDirectory(string $dir): ?string
    {
        if (!is_dir($dir)) {
            $this->error("Ordner nicht gefunden: {$dir}");
            return null;
        }

        try {
            $candidates = $this->csvCandidatesIn($dir);
        } catch (\UnexpectedValueException $e) {
            $this->error("Ordner kann nicht gelesen werden: {$dir} ({$e->getMessage()})");
            return null;
        }

        if ($candidates === []) {
            $this->error("Keine CSV/TXT in {$dir} (rekursiv) gefunden.");
            return null;
        }

        if (count($candidates) > 1) {
            $this->error("Mehrere CSV/TXT gefunden. Bitte eine mit --csv=... auswählen:");
            foreach ($candidates as $candidate) {
                $this->line(' - ' . $candidate);
            }
            return null;
        }

        return $candidates[0];
    }

    /**
     * @param string $dir
     * @return array<int, string>
     * @throws \UnexpectedValueException When the directory cannot be read.
     */
    private function csvCandidatesIn(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                rtrim($dir, "/\\"),
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $candidates = [];
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(csv|txt)$/i', $file->getFilename())) {
                $candidates[] = $file->getPathname();
            }
        }

        return $candidates;
    }

    /**
     * Keep or remove the imported file, as --keep-csv asks for.
     */
    private function handleCsvAfterImport(string $csvPath): void
    {
        if ($this->optionTruthy('keep-csv')) {
            $this->line("CSV/TXT behalten: {$csvPath}");
            return;
        }

        if (!is_file($csvPath)) {
            return;
        }

        if (@unlink($csvPath)) {
            $this->info("CSV/TXT gelöscht: {$csvPath}");
            return;
        }

        $this->warn("CSV/TXT konnte nicht gelöscht werden: {$csvPath}");
    }

    private function reportResult(ClipImportResult $result): void
    {
        $stats = $result->stats;
        $this->info("Import fertig: neu={$stats->created}, aktualisiert={$stats->updated}, Warnungen={$stats->warnings}");
        $this->line('Reihenfolge im Cron: ingest:scan → info:import (--dir oder --csv) → weekly:run');
    }

    private function optionTruthy(string $name): bool
    {
        $val = $this->option($name);
        if ($val === null) {
            return false;
        }
        $s = strtolower((string)$val);
        return in_array($s, ['1', 'true', 'on', 'yes', 'y'], true);
    }
}
