<?php

declare(strict_types=1);

namespace App\Pipelines\Ingest\Step;

use App\Enum\Ingest\IngestStepEnum;
use App\Pipelines\Ingest\Context\IngestContext;

class ValidateInputFileStep implements IngestStepInterface
{
    public function name(): IngestStepEnum
    {
        return IngestStepEnum::ValidateInputFile;
    }

    public function dependsOn(): array
    {
        return [];
    }

    public function isApplicable(IngestContext $context): bool
    {
        // TODO: Implement isApplicable() method.
    }

    public function handle(IngestContext $context): IngestContext
    {
        // TODO: Implement handle() method.
    }

}
