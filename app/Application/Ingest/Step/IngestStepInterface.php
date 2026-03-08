<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;
use App\Enum\Ingest\IngestStepEnum;

interface IngestStepInterface
{

    public function step(): IngestStepEnum;

    /**
     * @return array<IngestStepEnum>
     */
    public function dependsOn(): array;

    public function isApplicable(IngestContext $context): bool;


    public function handle(IngestContext $context): IngestContext;
}
