<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;

interface IngestStepInterface
{
    public function handle(IngestContext $context): IngestContext;
}
