<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Ingest;

use App\DTO\Ingest\IngestStatusDto;
use PHPUnit\Framework\TestCase;

final class IngestStatusDtoTest extends TestCase
{
    public function testItCreatesDtoWithCurrentStep(): void
    {
        $steps = [
            [
                'name' => 'uploadVideoToDropbox',
                'status' => 'failed',
                'error' => [
                    'type' => 'RuntimeException',
                    'message' => 'Dropbox: No refresh token configured.',
                ],
                'finishedAt' => '2026-03-29 12:30:56',
            ],
            [
                'name' => 'generatePreviewForClips',
                'status' => 'completed',
                'attempts' => 1,
                'error' => null,
                'finishedAt' => '2026-03-29 12:29:54',
            ],
            [
                'name' => 'lookupAndUpdateVideoHash',
                'status' => 'completed',
                'attempts' => 1,
                'error' => null,
                'finishedAt' => '2026-03-29 12:29:51',
            ],
        ];

        $dto = new IngestStatusDto(
            $steps,
            3,
            2,
            67,
            'generate_preview_for_clips',
        );

        self::assertSame($steps, $dto->steps);
        self::assertSame(3, $dto->totalSteps);
        self::assertSame(2, $dto->completedSteps);
        self::assertSame(67, $dto->progressPercent);
        self::assertSame('generate_preview_for_clips', $dto->currentStep);
    }

    public function testItCreatesDtoWithNullCurrentStep(): void
    {
        $steps = [];

        $dto = new IngestStatusDto(
            $steps,
            0,
            0,
            0,
            null,
        );

        self::assertSame($steps, $dto->steps);
        self::assertSame(0, $dto->totalSteps);
        self::assertSame(0, $dto->completedSteps);
        self::assertSame(0, $dto->progressPercent);
        self::assertNull($dto->currentStep);
    }
}
