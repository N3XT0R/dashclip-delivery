<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enum\Ingest\IngestStepEnum;
use App\Pipelines\Ingest\IngestPipeline;
use App\Pipelines\Ingest\Step\GeneratePreviewForVideoClipsStep;
use App\Pipelines\Ingest\Step\IngestStepInterface;
use App\Pipelines\Ingest\Step\LookupAndUpdateVideoHashStep;
use App\Pipelines\Ingest\Step\UploadVideoToDropboxStep;
use App\Services\Ingest\IngestStateService;
use Illuminate\Support\ServiceProvider;

final class IngestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IngestPipeline::class, function ($app) {
            $steps = collect($app->tagged('ingest.step'))
                ->sortBy(fn(IngestStepInterface $step) => array_search(
                    $step->name()->value,
                    array_map(fn($s) => $s->value, IngestStepEnum::order()),
                    true
                ))
                ->values()
                ->all();

            return new IngestPipeline(
                steps: $steps,
                ingestStateService: $app->make(IngestStateService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->app->tag([
            LookupAndUpdateVideoHashStep::class,
            GeneratePreviewForVideoClipsStep::class,
            UploadVideoToDropboxStep::class,
        ], 'ingest.step');
    }
}
