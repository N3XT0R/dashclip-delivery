<?php

declare(strict_types=1);

namespace App\Services\Ingest\Steps;

use App\Enum\Ingest\IngestResult;
use App\Exceptions\InvalidTimeRangeException;
use App\Exceptions\PreviewGenerationException;
use App\Services\Ingest\Contracts\IngestStepInterface;
use App\Services\Ingest\IngestContext;
use App\Services\PreviewService;
use Closure;
use Illuminate\Support\Facades\Log;

class GeneratePreviewStep implements IngestStepInterface
{
    public function __construct(private PreviewService $previewService)
    {
    }

    public function handle(IngestContext $context, Closure $next): IngestResult
    {
        try {
            // Output ggf. übernehmen (CLI-Kontext o.ä.)
            if (property_exists($context, 'output') && $context->output) {
                $this->previewService->setOutput($context->output);
            }

            $startSec = $context->startSec ?? 0;
            $endSec = $context->endSec ?? null;

            // Fallbacks, falls ImportCsvStep nichts gesetzt hat
            if ($endSec !== null && $endSec <= $startSec) {
                $endSec = null;
            }

            $context->previewUrl = $this->previewService->generatePreviewByDisk(
                $context->disk,
                $context->file->path,
                (int)$context->video?->getKey(),
                $startSec,
                $endSec
            );

            return $next($context);
        } catch (PreviewGenerationException $e) {
            Log::error('Preview generation failed', [
                'file' => $context->file->path,
                'video_id' => $context->video?->getKey(),
                'message' => $e->getMessage(),
                'context' => method_exists($e, 'context') ? $e->context() : [],
            ]);

            return IngestResult::ERR;
        } catch (InvalidTimeRangeException $e) {
            Log::error($e->getMessage(), $e->context());
        } catch (\Throwable $e) {
            Log::error('Unexpected error during preview generation', [
                'file' => $context->file->path,
                'exception' => $e,
            ]);

            return IngestResult::ERR;
        }
    }
}
