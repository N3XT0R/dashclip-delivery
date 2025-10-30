<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\FileInfoDto;
use App\Models\Assignment;
use App\ValueObjects\ClipImportResult;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class CsvService
{
    private const CSV_REGEX = '/\.(csv|txt)$/i';

    /**
     * Creates a CSV-String with info about the given Assignments and their Videos/Clips.
     * @param  Collection<Assignment>  $items  $items
     * @return string CSV-Content as String
     */
    public function buildInfoCsv(Collection $items): string
    {
        $rows = [];
        $rows[] = ['filename', 'hash', 'size_mb', 'start', 'end', 'note', 'bundle', 'role', 'submitted_by'];

        foreach ($items as $assignment) {
            $video = $assignment->video;
            $clips = $video->clips ?? collect();

            if ($clips->isEmpty()) {
                $rows[] = [
                    $video->original_name ?: basename($video->path),
                    $video->hash,
                    Number::fileSize($video->bytes),
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                ];
            } else {
                foreach ($clips as $clip) {
                    $rows[] = [
                        $video->original_name ?: basename($video->path),
                        $video->hash,
                        Number::fileSize($video->bytes),
                        isset($clip->start_sec) ? gmdate('i:s', (int)$clip->start_sec) : null,
                        isset($clip->end_sec) ? gmdate('i:s', (int)$clip->end_sec) : null,
                        $clip->note,
                        $clip->bundle_key,
                        $clip->role,
                        $clip->submitted_by,
                    ];
                }
            }
        }

        $fp = fopen('php://temp', 'w+');
        fwrite($fp, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($fp, $row, ';');
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }

    /**
     * List all CSV files in the given disk and base path.
     * @return Collection<FileInfoDto>
     */
    public function listCsvFiles(Filesystem $disk, string $basePath = ''): Collection
    {
        return collect($disk->files($basePath))
            ->filter(fn(string $path) => preg_match(self::CSV_REGEX, basename($path)))
            ->map(fn(string $path) => FileInfoDto::fromPath($path))
            ->values();
    }

    /**
     * Import CSV files from the given disk and base path.
     * @param  Filesystem  $disk
     * @param  string  $basePath
     * @return ClipImportResult|null
     */
    public function importCsvForDisk(
        Filesystem $disk,
        string $basePath = '',
        bool $deleteAfterSuccess = false
    ): ?ClipImportResult {
        $csvFiles = $this->listCsvFiles($disk, $basePath);
        if ($csvFiles->isEmpty()) {
            return null;
        }

        $aggregate = ClipImportResult::empty();
        $infoImporter = app(InfoImporter::class);

        foreach ($csvFiles as $csv) {
            $res = $infoImporter->importInfoFromDisk($disk, $csv->path);
            if ($res->stats->warnings === 0 && $deleteAfterSuccess) {
                $this->deleteCsvFile($disk, $csv);
            }
            $aggregate->merge($res);
        }

        return $aggregate;
    }


    public function deleteCsvFile(
        Filesystem $disk,
        FileInfoDto $infoDto
    ): bool {
        $result = false;
        $path = $infoDto->path;
        if ($infoDto->isCsv() && $disk->exists($path)) {
            $result = $disk->delete($path);
        }

        return $result;
    }

}
