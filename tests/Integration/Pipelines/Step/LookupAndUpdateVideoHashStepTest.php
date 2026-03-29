<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines\Step;

use App\Enum\Ingest\IngestStepEnum;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Pipelines\Ingest\Step\LookupAndUpdateVideoHashStep;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class LookupAndUpdateVideoHashStepTest extends DatabaseTestCase
{
    protected LookupAndUpdateVideoHashStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->step = $this->app->make(LookupAndUpdateVideoHashStep::class);
    }

    public function testItReturnsStepName(): void
    {
        self::assertSame(IngestStepEnum::LookupAndUpdateVideoHash, $this->step->name());
    }

    public function testItHasNoDependencies(): void
    {
        self::assertSame([], $this->step->dependsOn());
    }

    public function testItIsApplicableWhenVideoHasNoHashAndIsNotDuplicate(): void
    {
        $video = Video::factory()->make([
            'hash' => null,
        ]);

        $context = new IngestContext(
            video: $video,
            isDuplicate: false,
        );

        self::assertTrue($this->step->isApplicable($context));
    }

    public function testItIsNotApplicableWhenContextIsDuplicate(): void
    {
        $video = Video::factory()->make([
            'hash' => null,
        ]);

        $context = new IngestContext(
            video: $video,
            isDuplicate: true,
        );

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItIsNotApplicableWhenVideoAlreadyHasHash(): void
    {
        $video = Video::factory()->make([
            'hash' => 'existing-hash',
        ]);

        $context = new IngestContext(
            video: $video,
            isDuplicate: false,
        );

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItReturnsContextUnchangedWhenVideoAlreadyHasHash(): void
    {
        $video = Video::factory()->create([
            'hash' => 'existing-hash',
        ]);

        $context = new IngestContext(
            video: $video,
            hash: null,
            isDuplicate: false,
        );

        $result = $this->step->handle($context);

        $video->refresh();

        self::assertSame($context, $result);
        self::assertSame($video->getKey(), $result->video->getKey());
        self::assertSame('existing-hash', $video->hash);
        self::assertNull($result->hash);
        self::assertFalse($result->isDuplicate);
    }

    public function testItCalculatesAndPersistsHashWhenVideoHasNoHash(): void
    {
        Storage::fake('videos');
        Storage::disk('videos')->put('ingest/test-video.mp4', 'integration-test-content');

        $video = Video::factory()->create([
            'disk' => 'videos',
            'path' => 'ingest/test-video.mp4',
            'hash' => null,
        ]);

        $context = new IngestContext(
            video: $video,
            hash: null,
            isDuplicate: false,
        );

        $result = $this->step->handle($context);

        $video->refresh();

        self::assertSame($video->getKey(), $result->video->getKey());
        self::assertNotNull($result->hash);
        self::assertNotSame('', $result->hash);
        self::assertSame($result->hash, $video->hash);
        self::assertFalse($result->isDuplicate);
    }

    public function testItUsesHashFromContextWhenProvided(): void
    {
        $video = Video::factory()->create([
            'hash' => null,
        ]);

        $context = new IngestContext(
            video: $video,
            hash: 'precomputed-hash',
            isDuplicate: false,
        );

        $result = $this->step->handle($context);

        $video->refresh();

        self::assertSame($video->getKey(), $result->video->getKey());
        self::assertSame('precomputed-hash', $result->hash);
        self::assertSame('precomputed-hash', $video->hash);
        self::assertFalse($result->isDuplicate);
    }

    public function testItMarksContextAsDuplicateWhenHashAlreadyExists(): void
    {
        $existingVideo = Video::factory()->create([
            'hash' => 'duplicate-hash',
        ]);

        $duplicateVideo = Video::factory()->create([
            'hash' => null,
        ]);

        $context = new IngestContext(
            video: $duplicateVideo,
            hash: 'duplicate-hash',
            isDuplicate: false,
        );

        $result = $this->step->handle($context);

        self::assertSame($duplicateVideo->getKey(), $result->video->getKey());
        self::assertSame('duplicate-hash', $result->hash);
        self::assertTrue($result->isDuplicate);

        $duplicateVideo = Video::query()->find($duplicateVideo->getKey());

        self::assertNull($duplicateVideo);
        self::assertNotNull($existingVideo->fresh());
    }
}
