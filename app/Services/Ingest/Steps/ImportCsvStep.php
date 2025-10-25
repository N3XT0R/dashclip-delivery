<?php

declare(strict_types=1);

namespace App\Services\Ingest\Steps;

use App\Enum\Ingest\IngestResult;
use App\Services\CsvService;
use App\Services\Ingest\Contracts\IngestStepInterface;
use App\Services\Ingest\IngestContext;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportCsvStep implements IngestStepInterface
{
    public function __construct(private CsvService $csvService)
    {
    }

    public function handle(IngestContext $context, Closure $next): IngestResult
    {
        try {
            // CSV import für das aktuelle Verzeichnis (wie vorher im Scanner)
            $importResult = $this->csvService->importCsvForDisk($context->disk);

            // Video-Instanz sollte bereits vom vorherigen Step gesetzt worden sein
            if ($context->video) {
                $clip = $importResult?->clipsForVideo($context->video)->first();
                if ($clip) {
                    // Start/Endzeiten direkt im Context verfügbar machen
                    $context->clip = $clip;
                    $context->startSec = $clip->start_sec;
                    $context->endSec = $clip->end_sec;
                }
            }

            return $next($context);
        } catch (Throwable $e) {
            Log::warning('CSV import failed during ingest', [
                'file' => $context->file->path,
                'exception' => $e,
            ]);

            return IngestResult::ERR;
        }
    }
}
