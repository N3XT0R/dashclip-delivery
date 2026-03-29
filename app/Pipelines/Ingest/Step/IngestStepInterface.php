<?php

declare(strict_types=1);

namespace App\Pipelines\Ingest\Step;

use App\Enum\Ingest\IngestStepEnum;
use App\Pipelines\Ingest\Context\IngestContext;

interface IngestStepInterface
{

    public function name(): IngestStepEnum;

    /**
     * @return array<IngestStepEnum>
     */
    public function dependsOn(): array;

    public function isApplicable(IngestContext $context): bool;


    public function handle(IngestContext $context): IngestContext;
}
