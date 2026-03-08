<?php

declare(strict_types=1);

namespace App\Application\Ingest\Step;

use App\Application\Ingest\Context\IngestContext;

interface IngestStepInterface
{

    public function name(): string;

    /**
     * @return array<string>
     */
    public function dependsOn(): array;


    public function handle(IngestContext $context): IngestContext;
}
