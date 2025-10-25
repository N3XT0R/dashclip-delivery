<?php

declare(strict_types=1);

namespace App\Services\Ingest\Steps;

use App\Enum\Ingest\IngestResult;
use App\Facades\PathBuilder;
use App\Services\Ingest\Contracts\IngestStepInterface;
use App\Services\Ingest\IngestContext;
use App\Services\Upload\UploadService;
use App\Services\VideoService;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

class UploadStep implements IngestStepInterface
{
    public function __construct(
        private UploadService $uploadService,
        private VideoService $videoService
    ) {
    }

    public function handle(IngestContext $context, Closure $next): IngestResult
    {
        try {
            $video = $context->video;
            if (!$video) {
                Log::warning('Upload skipped: missing video instance', [
                    'file' => $context->file->path,
                ]);
                return IngestResult::ERR;
            }

            $hash = $context->hash ?? $video->hash;
            $ext = $context->file->extension;
            $dstRel = PathBuilder::forVideo($hash, $ext);

            // Upload durchführen
            $this->uploadService->uploadFile($context->disk, $context->file->path, $context->targetDisk);

            // Video-Service: Abschluss des Upload-Vorgangs
            $this->videoService->finalizeUpload(
                $video,
                $dstRel,
                $context->targetDisk,
                $context->previewUrl
            );

            // Logging + Kontextanreicherung
            Log::info('Upload abgeschlossen', [
                'video_id' => $video->getKey(),
                'disk' => $video->disk,
                'path' => $video->path,
                'preview' => $context->previewUrl,
                'source_file' => $context->file->path,
            ]);

            $context->finalPath = $dstRel;
            return $next($context);
        } catch (Throwable $e) {
            Log::error('UploadStep failed', [
                'file' => $context->file->path,
                'exception' => $e,
            ]);

            return IngestResult::ERR;
        }
    }
}
