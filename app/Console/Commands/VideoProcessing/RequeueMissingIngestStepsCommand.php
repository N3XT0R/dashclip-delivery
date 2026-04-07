<?php

declare(strict_types=1);

namespace App\Console\Commands\VideoProcessing;

use App\Enum\Ingest\IngestStepEnum;
use App\Enum\ProcessingStatusEnum;
use App\Pipelines\Ingest\IngestPipeline;
use App\Repository\VideoRepository;
use App\Services\Ingest\IngestStateService;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'video-processing:requeue-missing-ingest-steps',
    description: <<<'DESCRIPTION'
Requeue completed videos that are missing ingest steps introduced after the original processing run
DESCRIPTION,
)]
class RequeueMissingIngestStepsCommand extends AbstractRequeueVideosCommand
{
    public function __construct(
        private readonly IngestPipeline $ingestPipeline,
        private readonly IngestStateService $ingestStateService,
    ) {
        parent::__construct();
    }

    protected function getVideos(VideoRepository $videoRepository): LazyCollection
    {
        $requiredSteps = $this->getRequiredSteps();

        return $videoRepository
            ->getLazyForRequeue(
                now(),
                ProcessingStatusEnum::Completed,
                chunkSize: 50,
            )
            ->filter(fn($video) => $this->ingestStateService->hasMissingSteps($video, $requiredSteps));
    }

    protected function getErrorLogMessage(): string
    {
        return 'Error requeuing completed videos with missing ingest steps';
    }

    /**
     * Get the list of required ingest steps based on the current pipeline configuration
     * @return list<IngestStepEnum>
     */
    private function getRequiredSteps(): array
    {
        $steps = [];

        foreach ($this->ingestPipeline->getSteps() as $step) {
            $steps[] = $step->name();
        }

        return $steps;
    }
}
