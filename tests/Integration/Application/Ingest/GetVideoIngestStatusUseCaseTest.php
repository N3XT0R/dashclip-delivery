<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Ingest;

use App\Application\Ingest\GetVideoIngestStatusUseCase;
use App\DTO\Ingest\IngestStatusDto;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class GetVideoIngestStatusUseCaseTest extends DatabaseTestCase
{
    private GetVideoIngestStatusUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = $this->app->make(GetVideoIngestStatusUseCase::class);
    }

    public function testItReturnsNullWhenVideoDoesNotExist(): void
    {
        $result = $this->useCase->handle(999999);

        self::assertNull($result);
    }

    public function testItBuildsIngestStatusFromVideoId(): void
    {
        $video = Video::factory()->create([
            'meta' => [
                'ingest' => [
                    'steps' => [
                        'upload_video_to_dropbox' => [
                            'error' => [
                                'type' => 'RuntimeException',
                                'message' => 'Dropbox: No refresh token configured.',
                            ],
                            'status' => 'failed',
                            'finished_at' => '2026-03-29 12:35:58',
                        ],
                        'generate_preview_for_clips' => [
                            'error' => null,
                            'status' => 'completed',
                            'attempts' => 1,
                            'finished_at' => '2026-03-29 12:29:54',
                        ],
                        'lookup_and_update_video_hash' => [
                            'error' => null,
                            'status' => 'completed',
                            'attempts' => 1,
                            'finished_at' => '2026-03-29 12:29:51',
                        ],
                    ],
                    'current_step' => 'generate_preview_for_clips',
                ],
            ],
        ]);

        $result = $this->useCase->handle($video->getKey());

        self::assertInstanceOf(IngestStatusDto::class, $result);
        self::assertSame('generate_preview_for_clips', $result->currentStep);

        self::assertGreaterThan(0, $result->totalSteps);
        self::assertCount($result->totalSteps, $result->steps);
        self::assertSame(2, $result->completedSteps);
    }

    public function testItBuildsIngestStatusFromVideoInstance(): void
    {
        $video = Video::factory()->create([
            'meta' => [
                'ingest' => [
                    'steps' => [
                        'upload_video_to_dropbox' => [
                            'status' => 'pending',
                        ],
                        'generate_preview_for_clips' => [
                            'status' => 'completed',
                            'attempts' => 3,
                            'finished_at' => '2026-03-29 12:29:54',
                        ],
                        'lookup_and_update_video_hash' => [
                            'status' => 'completed',
                            'attempts' => 1,
                            'finished_at' => '2026-03-29 12:29:51',
                        ],
                    ],
                    'current_step' => 'upload_video_to_dropbox',
                ],
            ],
        ]);

        $result = $this->useCase->handle($video);

        self::assertInstanceOf(IngestStatusDto::class, $result);
        self::assertSame('upload_video_to_dropbox', $result->currentStep);
        self::assertSame(2, $result->completedSteps);
        self::assertGreaterThan(0, $result->totalSteps);
    }

    public function testItUsesDefaultsWhenIngestMetaIsMissing(): void
    {
        $video = Video::factory()->create([
            'meta' => [],
        ]);

        $result = $this->useCase->handle($video);

        self::assertInstanceOf(IngestStatusDto::class, $result);
        self::assertNull($result->currentStep);
        self::assertSame(0, $result->completedSteps);

        foreach ($result->steps as $step) {
            self::assertSame('pending', $step->status);
            self::assertSame(0, $step->attempts);
            self::assertFalse($step->isCurrent);
        }
    }

    public function testItUsesDefaultsWhenIngestMetaStructureIsInvalid(): void
    {
        $video = Video::factory()->create([
            'meta' => [
                'ingest' => [
                    'steps' => 'invalid',
                    'current_step' => 123,
                ],
            ],
        ]);

        $result = $this->useCase->handle($video);

        self::assertInstanceOf(IngestStatusDto::class, $result);
        self::assertNull($result->currentStep);
        self::assertSame(0, $result->completedSteps);

        foreach ($result->steps as $step) {
            self::assertSame('pending', $step->status);
            self::assertSame(0, $step->attempts);
            self::assertFalse($step->isCurrent);
        }
    }
}
