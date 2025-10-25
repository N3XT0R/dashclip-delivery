<?php

declare(strict_types=1);

namespace App\Services\Ingest\Steps;

use App\Enum\Ingest\IngestResult;
use App\Facades\DynamicStorage;
use App\Services\Ingest\Contracts\IngestStepInterface;
use App\Services\Ingest\IngestContext;
use App\Services\VideoService;
use Closure;

class CreateVideoStep implements IngestStepInterface
{
    public function __construct(private VideoService $videoService)
    {
    }

    public function handle(IngestContext $context, Closure $next): IngestResult
    {
        $context->hash = DynamicStorage::getHashForFileInfoDto($context->disk, $context->file);
        $context->video = $this->videoService->createVideoBydDiskAndFileInfoDto(
            'dynamicStorage',
            $context->disk,
            $context->file
        );

        return $next($context);
    }
}
