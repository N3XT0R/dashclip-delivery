<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Ingest;

use App\DTO\Ingest\IngestStepStatusDto;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class IngestStepStatusDtoTest extends TestCase
{
    public function testItCreatesDtoWithAllValues(): void
    {
        $finishedAt = new DateTimeImmutable('2026-03-29 12:30:56');

        $dto = new IngestStepStatusDto(
            'uploadVideoToDropbox',
            'failed',
            $finishedAt,
            2,
            true,
        );

        self::assertSame('uploadVideoToDropbox', $dto->name);
        self::assertSame('failed', $dto->status);
        self::assertSame($finishedAt, $dto->finishedAt);
        self::assertSame(2, $dto->attempts);
        self::assertTrue($dto->isCurrent);
    }

    public function testItUsesDefaultValues(): void
    {
        $finishedAt = new DateTimeImmutable('2026-03-29 12:29:54');

        $dto = new IngestStepStatusDto(
            'generatePreviewForClips',
            'completed',
            $finishedAt,
        );

        self::assertSame('generatePreviewForClips', $dto->name);
        self::assertSame('completed', $dto->status);
        self::assertSame($finishedAt, $dto->finishedAt);
        self::assertSame(0, $dto->attempts);
        self::assertFalse($dto->isCurrent);
    }
}
