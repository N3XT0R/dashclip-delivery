<?php

declare(strict_types=1);

use App\Services\Ingest\Steps\CreateVideoStep;
use App\Services\Ingest\Steps\FinalizeStep;
use App\Services\Ingest\Steps\GeneratePreviewStep;
use App\Services\Ingest\Steps\ImportCsvStep;
use App\Services\Ingest\Steps\UploadStep;

return [
    'steps' => [
        CreateVideoStep::class,
        ImportCsvStep::class,
        GeneratePreviewStep::class,
        UploadStep::class,
        FinalizeStep::class,
    ],
];