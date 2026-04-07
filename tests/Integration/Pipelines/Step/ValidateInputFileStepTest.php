<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines\Step;

use App\Enum\Ingest\IngestStepEnum;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Pipelines\Ingest\Step\ValidateInputFileStep;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class ValidateInputFileStepTest extends DatabaseTestCase
{
    protected ValidateInputFileStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->step = $this->app->make(ValidateInputFileStep::class);
    }

    public function testNameReturnsValidateInputFileEnum(): void
    {
        self::assertSame(IngestStepEnum::ValidateInputFile, $this->step->name());
    }

    public function testDependsOnReturnsEmptyArray(): void
    {
        self::assertSame([], $this->step->dependsOn());
    }

    public function testIsApplicableReturnsTrueWhenContextIsNeitherDuplicateNorInvalid(): void
    {
        $video = Video::factory()->create();

        $context = $this->createContext(
            $video,
            isDuplicate: false,
            isInvalid: false,
        );

        self::assertTrue($this->step->isApplicable($context));
    }

    public function testIsApplicableReturnsFalseWhenContextIsDuplicate(): void
    {
        $video = Video::factory()->create();

        $context = $this->createContext(
            $video,
            isDuplicate: true,
            isInvalid: false,
        );

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testIsApplicableReturnsFalseWhenContextIsInvalid(): void
    {
        $video = Video::factory()->create();

        $context = $this->createContext(
            $video,
            isDuplicate: false,
            isInvalid: true,
        );

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testIsApplicableReturnsFalseWhenContextIsDuplicateAndInvalid(): void
    {
        $video = Video::factory()->create();

        $context = $this->createContext(
            $video,
            isDuplicate: true,
            isInvalid: true,
        );

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testHandleMarksContextAsInvalidAndSoftDeletesVideoWhenDiskIsMissing(): void
    {
        Storage::fake('ingest');

        $video = Video::factory()->create([
            'disk' => null,
            'path' => 'videos/test-video.mp4',
        ]);

        $context = $this->createContext($video);

        $result = $this->step->handle($context);

        self::assertTrue($result->isInvalid);

        $video->refresh();

        self::assertNotNull($video->deleted_at);
        $this->assertSoftDeleted('videos', [
            'id' => $video->getKey(),
        ]);
    }

    public function testHandleMarksContextAsInvalidAndSoftDeletesVideoWhenPathIsMissing(): void
    {
        Storage::fake('ingest');

        $video = Video::factory()->create([
            'disk' => 'ingest',
            'path' => null,
        ]);

        $context = $this->createContext($video);

        $result = $this->step->handle($context);

        self::assertTrue($result->isInvalid);

        $video->refresh();

        self::assertNotNull($video->deleted_at);
        $this->assertSoftDeleted('videos', [
            'id' => $video->getKey(),
        ]);
    }

    public function testHandleMarksContextAsInvalidAndSoftDeletesVideoWhenFileDoesNotExist(): void
    {
        Storage::fake('ingest');

        $video = Video::factory()->create([
            'disk' => 'ingest',
            'path' => 'videos/missing-video.mp4',
        ]);

        $context = $this->createContext($video);

        $result = $this->step->handle($context);

        self::assertTrue($result->isInvalid);

        $video->refresh();

        self::assertNotNull($video->deleted_at);
        $this->assertSoftDeleted('videos', [
            'id' => $video->getKey(),
        ]);
    }

    public function testHandleKeepsVideoAndLeavesContextValidWhenFileExists(): void
    {
        Storage::fake('ingest');
        Storage::disk('ingest')->put('videos/test-video.mp4', 'test-video-content');

        $video = Video::factory()->create([
            'disk' => 'ingest',
            'path' => 'videos/test-video.mp4',
        ]);

        $context = $this->createContext($video);

        $result = $this->step->handle($context);

        self::assertFalse($result->isInvalid);
        self::assertSame($video->getKey(), $result->video->getKey());

        $video->refresh();

        self::assertNull($video->deleted_at);
        $this->assertDatabaseHas('videos', [
            'id' => $video->getKey(),
            'deleted_at' => null,
        ]);
    }

    private function createContext(
        Video $video,
        bool $isDuplicate = false,
        bool $isInvalid = false,
    ): IngestContext {
        return new IngestContext(
            video: $video,
            hash: null,
            isDuplicate: $isDuplicate,
            isInvalid: $isInvalid,
        );
    }
}
