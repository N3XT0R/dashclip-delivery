<?php

declare(strict_types=1);

namespace App\Services\Ingest\Contracts;

use App\Enum\Ingest\IngestResult;
use App\Services\Ingest\IngestContext;

interface IngestPipelineInterface
{
    public function handle(IngestContext $context): IngestResult;
}