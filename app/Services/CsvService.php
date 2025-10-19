<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\FileInfoDto;
use App\Models\Assignment;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class CsvService
{
    private const CSV_REGEX = '/\.(csv|txt)$/i';

    public function __construct(private InfoImporter $infoImporter)
    {
    }


    /**
     * @param  Collection<Assignment>  $items
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
                    number_format(($video->bytes ?? 0) / 1048576, 1, '.', ''),
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
                        number_format(($video->bytes ?? 0) / 1048576, 1, '.', ''),
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
     * Listet alle CSV/Text-Dateien im angegebenen Ordner (nicht rekursiv).
     * @return Collection<FileInfoDto>
     */
    public function listCsvFiles(Filesystem $disk, string $basePath = ''): Collection
    {
        return collect($disk->files($basePath))
            ->filter(fn(string $path) => preg_match(self::CSV_REGEX, basename($path)))
            ->map(fn(string $path) => FileInfoDto::fromPath($path))
            ->values();
    }

    public function importCsvForDisk(Filesystem $disk, string $basePath = ''): void
    {
        $csvFiles = $this->listCsvFiles($disk, $basePath);
        foreach ($csvFiles as $csv) {
            $this->infoImporter->importInfoFromDisk($disk, $csv->path);
        }
    }

}
