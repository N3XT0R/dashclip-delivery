<?php

declare(strict_types=1);

namespace Tests\Unit\Pipelines\Ingest\Context;

use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class IngestContextTest extends TestCase
{
    public function testItUsesProvidedValues(): void
    {
        $video = new Video();
        $video->clips = collect(['videoClip']);

        $clips = collect(['customClip']);

        $context = new IngestContext(
            video: $video,
            clips: $clips,
            hash: 'abc123',
            isDuplicate: true,
        );

        self::assertSame($video, $context->video);
        self::assertSame($clips, $context->clips);
        self::assertSame('abc123', $context->hash);
        self::assertTrue($context->isDuplicate);
    }

    public function testItUsesVideoClipsWhenNoClipsAreProvided(): void
    {
        $video = new Video();
        $video->clips = collect(['clipOne', 'clipTwo']);

        $context = new IngestContext(
            video: $video,
        );

        self::assertSame($video, $context->video);
        self::assertInstanceOf(Collection::class, $context->clips);
        self::assertSame($video->clips, $context->clips);
        self::assertNull($context->hash);
        self::assertFalse($context->isDuplicate);
    }

    public function testItUsesEmptyCollectionWhenNoClipsAreAvailable(): void
    {
        $video = new Video();

        $context = new IngestContext(
            video: $video,
        );

        self::assertSame($video, $context->video);
        self::assertInstanceOf(Collection::class, $context->clips);
        self::assertCount(0, $context->clips);
        self::assertNull($context->hash);
        self::assertFalse($context->isDuplicate);
    }
}
