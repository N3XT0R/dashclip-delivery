<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines\Step;

use App\Pipelines\Ingest\Step\ValidateInputFileStep;
use Tests\DatabaseTestCase;

class ValidateInputFileStepTest extends DatabaseTestCase
{
    protected ValidateInputFileStep $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->step = $this->app->make(ValidateInputFileStep::class);
    }
}
