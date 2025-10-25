<?php

declare(strict_types=1);

namespace App\Services\Ingest\Steps;

use App\Enum\Ingest\IngestResult;
use App\Services\BatchService;
use App\Services\Ingest\Contracts\IngestStepInterface;
use App\Services\Ingest\IngestContext;
use App\ValueObjects\IngestStats;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FinalizeStep implements IngestStepInterface
{
    public function __construct(private BatchService $batchService)
    {
    }

    public function handle(IngestContext $context, Closure $next): IngestResult
    {
        try {
            DB::commit(); // falls vorher Transaktionen liefen

            $stats = $context->stats ?? new IngestStats();
            $result = $context->result ?? IngestResult::NEW;
            $batch = $context->batch ?? null;

            // Statistiken aktualisieren
            $stats->increment($result);
            if ($batch) {
                $this->batchService->updateStats($batch, $stats);
                $this->batchService->finalizeStats($batch, $stats);
            }

            Log::info('FinalizeStep completed', [
                'video_id' => $context->video?->getKey(),
                'result' => $result->name,
                'path' => $context->finalPath ?? null,
                'preview' => $context->previewUrl ?? null,
            ]);

            // Sauberer Abschluss — optional kann man hier cleanup oder move/delete vornehmen
            return $next($context);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('FinalizeStep failed', [
                'video_id' => $context->video?->getKey(),
                'exception' => $e,
            ]);

            return IngestResult::ERR;
        }
    }
}
