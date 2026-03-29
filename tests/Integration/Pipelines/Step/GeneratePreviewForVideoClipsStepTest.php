<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines\Step;

use App\Enum\Ingest\IngestStepEnum;
use App\Models\Clip;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Pipelines\Ingest\Step\GeneratePreviewForVideoClipsStep;
use App\Repository\ClipRepository;
use App\Services\PreviewService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\DatabaseTestCase;

final class GeneratePreviewForVideoClipsStepTest extends DatabaseTestCase
{
    private GeneratePreviewForVideoClipsStep $step;

    private PreviewService|MockInterface $previewService;

    private ClipRepository|MockInterface $clipRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previewService = Mockery::mock(PreviewService::class);
        $this->clipRepository = Mockery::mock(ClipRepository::class);

        $this->step = new GeneratePreviewForVideoClipsStep(
            $this->previewService,
            $this->clipRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testItReturnsStepName(): void
    {
        self::assertSame(
            IngestStepEnum::GeneratePreviewForVideoClips,
            $this->step->name(),
        );
    }

    public function testItReturnsDependencies(): void
    {
        self::assertSame(
            [IngestStepEnum::LookupAndUpdateVideoHash],
            $this->step->dependsOn(),
        );
    }

    public function testItIsApplicableWhenContextIsNotDuplicateAndClipsExist(): void
    {
        $video = Video::factory()->make();
        $clips = collect([
            Clip::factory()->make(),
        ]);

        $context = new IngestContext(
            video: $video,
            clips: $clips,
            isDuplicate: false,
        );

        self::assertTrue($this->step->isApplicable($context));
    }

    public function testItIsNotApplicableWhenContextIsDuplicate(): void
    {
        $video = Video::factory()->make();
        $clips = collect([
            Clip::factory()->make(),
        ]);

        $context = new IngestContext(
            video: $video,
            clips: $clips,
            isDuplicate: true,
        );

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItIsNotApplicableWhenClipsAreNull(): void
    {
        $video = Video::factory()->make();
        $video->clips = null;

        $context = new IngestContext(
            video: $video,
            clips: null,
            isDuplicate: false,
        );

        $context->clips = null;

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItIsNotApplicableWhenClipsAreEmpty(): void
    {
        $video = Video::factory()->make();

        $context = new IngestContext(
            video: $video,
            clips: collect(),
            isDuplicate: false,
        );

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItReturnsContextUnchangedWhenContextIsDuplicate(): void
    {
        $video = Video::factory()->make();
        $clip = Clip::factory()->make();

        $context = new IngestContext(
            video: $video,
            clips: collect([$clip]),
            isDuplicate: true,
        );

        $result = $this->step->handle($context);

        self::assertSame($context, $result);
        self::assertSame($context->clips, $result->clips);
        self::assertTrue($result->isDuplicate);
    }

    public function testItReturnsContextUnchangedWhenClipsAreNull(): void
    {
        $video = Video::factory()->make();
        $video->clips = null;

        $context = new IngestContext(
            video: $video,
            clips: null,
            isDuplicate: false,
        );

        $context->clips = null;

        $result = $this->step->handle($context);

        self::assertSame($context, $result);
        self::assertNull($result->clips);
        self::assertFalse($result->isDuplicate);
    }

    public function testItReturnsContextUnchangedWhenClipsAreEmpty(): void
    {
        $video = Video::factory()->make();

        $context = new IngestContext(
            video: $video,
            clips: collect(),
            isDuplicate: false,
        );

        $result = $this->step->handle($context);

        self::assertSame($context, $result);
        self::assertCount(0, $result->clips);
        self::assertFalse($result->isDuplicate);
    }

    public function testItGeneratesPreviewAndUpdatesAllClips(): void
    {
        Config::set('preview.default_disk', 'preview-testing');
        Storage::fake('preview-testing');

        $video = Video::factory()->make();

        $clipOne = Clip::factory()->make([
            'preview_path' => null,
            'preview_disk' => null,
        ]);

        $clipTwo = Clip::factory()->make([
            'preview_path' => null,
            'preview_disk' => null,
        ]);

        $clips = collect([$clipOne, $clipTwo]);

        $context = new IngestContext(
            video: $video,
            clips: $clips,
            isDuplicate: false,
        );

        $this->previewService
            ->shouldReceive('generatePreviewForClip')
            ->once()
            ->with($clipOne, Mockery::type(get_class(Storage::disk('preview-testing'))))
            ->andReturn('previews/clip-one.jpg');

        $this->previewService
            ->shouldReceive('generatePreviewForClip')
            ->once()
            ->with($clipTwo, Mockery::type(get_class(Storage::disk('preview-testing'))))
            ->andReturn('previews/clip-two.jpg');

        $this->clipRepository
            ->shouldReceive('update')
            ->once()
            ->with($clipOne, [
                'preview_path' => 'previews/clip-one.jpg',
                'preview_disk' => 'preview-testing',
            ]);

        $this->clipRepository
            ->shouldReceive('update')
            ->once()
            ->with($clipTwo, [
                'preview_path' => 'previews/clip-two.jpg',
                'preview_disk' => 'preview-testing',
            ]);

        $result = $this->step->handle($context);

        self::assertSame($context, $result);

        self::assertSame('previews/clip-one.jpg', $clipOne->preview_path);
        self::assertSame('preview-testing', $clipOne->preview_disk);

        self::assertSame('previews/clip-two.jpg', $clipTwo->preview_path);
        self::assertSame('preview-testing', $clipTwo->preview_disk);
    }
}
