<?php

declare(strict_types=1);

namespace App\Console\Commands;

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
        $csvPath = (string)($this->option('csv') ?? '');
        $dir = (string)($this->option('dir') ?? '');

        if ($csvPath === '' && $dir === '') {
            $this->error('Gib entweder --dir=/pfad/zum/ordner ODER --csv=/pfad/zur/datei.csv an.');
            return self::FAILURE;
        }

        // Wenn nur --dir angegeben ist: rekursiv nach genau 1 CSV/TXT suchen
        if ($dir !== '' && $csvPath === '') {
            if (!is_dir($dir)) {
                $this->error("Ordner nicht gefunden: {$dir}");
                return self::FAILURE;
            }

            $base = rtrim($dir, "/\\");
            $candidates = [];

            try {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $base,
                        \FilesystemIterator::SKIP_DOTS
                        | \FilesystemIterator::FOLLOW_SYMLINKS
                    ),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                /** @var \SplFileInfo $file */
                foreach ($it as $file) {
                    if ($file->isFile() && preg_match('/\.(csv|txt)$/i', $file->getFilename())) {
                        $candidates[] = $file->getPathname();
                    }
                }
            } catch (\UnexpectedValueException $e) {
                $this->error("Ordner kann nicht gelesen werden: {$dir} ({$e->getMessage()})");
                return self::FAILURE;
            }

            if (count($candidates) === 0) {
                $this->error("Keine CSV/TXT in {$dir} (rekursiv) gefunden.");
                return self::FAILURE;
            }
            if (count($candidates) > 1) {
                $this->error("Mehrere CSV/TXT gefunden. Bitte eine mit --csv=... auswählen:");
                foreach ($candidates as $c) {
                    $this->line(' - '.$c);
                }
                return self::FAILURE;
            }

            $csvPath = $candidates[0];
        }

        if (!is_file($csvPath)) {
            $this->error("CSV nicht gefunden: {$csvPath}");
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
                fn($msg) => $this->warn($msg)
            );

            // CSV nur löschen, wenn nicht --keep-csv=1
            if (!$this->optionTruthy('keep-csv') && is_file($csvPath)) {
                if (@unlink($csvPath)) {
                    $this->info("CSV/TXT gelöscht: {$csvPath}");
                } else {
                    $this->warn("CSV/TXT konnte nicht gelöscht werden: {$csvPath}");
                }
            } elseif ($this->optionTruthy('keep-csv')) {
                $this->line("CSV/TXT behalten: {$csvPath}");
            }

            $stats = $result->stats;
            $this->info("Import fertig: neu={$stats->created}, aktualisiert={$stats->updated}, Warnungen={$stats->warnings}");
            $this->line('Reihenfolge im Cron: ingest:scan → info:import (--dir oder --csv) → weekly:run');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
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
