<?php

declare(strict_types=1);

namespace App\Services\Ingest\Contracts;

use App\Enum\Ingest\IngestResult;
use App\Services\Ingest\IngestContext;
use Closure;

interface IngestStepInterface
{
    public function handle(IngestContext $context, Closure $next): IngestResult;
}
