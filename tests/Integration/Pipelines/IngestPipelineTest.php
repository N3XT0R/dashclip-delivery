<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines;

use App\Enum\Ingest\IngestStepEnum;
use App\Enum\ProcessingStatusEnum;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Pipelines\Ingest\IngestPipeline;
use App\Pipelines\Ingest\Step\LookupAndUpdateVideoHashStep;
use App\Services\Ingest\IngestStateService;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class IngestPipelineTest extends DatabaseTestCase
{
    private IngestPipeline $pipeline;

    private IngestStateService $ingestStateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ingestStateService = $this->app->make(IngestStateService::class);

        $this->pipeline = new IngestPipeline(
            steps: [
                $this->app->make(LookupAndUpdateVideoHashStep::class),
            ],
            ingestStateService: $this->ingestStateService,
        );
    }

    public function testItReturnsConfiguredSteps(): void
    {
        $steps = array_values(iterator_to_array($this->pipeline->getSteps()));

        self::assertCount(1, $steps);
        self::assertInstanceOf(LookupAndUpdateVideoHashStep::class, $steps[0]);
    }

    public function testItProcessesLookupAndUpdateVideoHashStep(): void
    {
        Storage::fake('ingest');

        Storage::disk('ingest')->put('videos/test-video.mp4', 'test-video-content');

        $video = Video::factory()->create([
            'disk' => 'ingest',
            'path' => 'videos/test-video.mp4',
            'hash' => null,
        ]);

        $context = $this->createContext($video);

        $result = $this->pipeline->handle($context);

        $video->refresh();

        self::assertSame($video->getKey(), $result->video->getKey());
        self::assertFalse($result->isDuplicate);
        self::assertNotNull($result->hash);
        self::assertSame($result->hash, $video->hash);

        self::assertTrue(
            $this->ingestStateService->isStepCompleted(
                $video,
                IngestStepEnum::LookupAndUpdateVideoHash,
            )
        );

        self::assertSame(ProcessingStatusEnum::Completed, $video->processing_status);
    }

    public function testItSkipsLookupStepWhenVideoAlreadyHasHash(): void
    {
        $video = Video::factory()->create([
            'hash' => 'existing-hash',
        ]);

        $context = $this->createContext($video);

        $result = $this->pipeline->handle($context);

        $video->refresh();

        self::assertSame($video->getKey(), $result->video->getKey());
        self::assertNull($result->hash);
        self::assertFalse($result->isDuplicate);
        self::assertSame('existing-hash', $video->hash);

        self::assertFalse(
            $this->ingestStateService->isStepCompleted(
                $video,
                IngestStepEnum::LookupAndUpdateVideoHash,
            )
        );

        self::assertSame(ProcessingStatusEnum::Completed, $video->processing_status);
    }

    public function testItSkipsAlreadyCompletedStepOnRetry(): void
    {
        Storage::fake('ingest');

        Storage::disk('ingest')->put('videos/test-video.mp4', 'test-video-content');

        $video = Video::factory()->create([
            'disk' => 'ingest',
            'path' => 'videos/test-video.mp4',
            'hash' => null,
        ]);

        $context = $this->createContext($video);

        $firstResult = $this->pipeline->handle($context);
        $secondResult = $this->pipeline->handle($firstResult);

        $video->refresh();

        self::assertSame($video->getKey(), $secondResult->video->getKey());

        self::assertTrue(
            $this->ingestStateService->isStepCompleted(
                $video,
                IngestStepEnum::LookupAndUpdateVideoHash,
            )
        );

        self::assertSame(ProcessingStatusEnum::Completed, $video->processing_status);
    }

    private function createContext(Video $video): IngestContext
    {
        return new IngestContext(
            video: $video,
            hash: null,
            isDuplicate: false,
        );
    }
}
