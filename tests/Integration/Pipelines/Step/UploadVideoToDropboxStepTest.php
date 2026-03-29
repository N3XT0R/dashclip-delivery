<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines\Step;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\Ingest\IngestStepEnum;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Pipelines\Ingest\Step\UploadVideoToDropboxStep;
use App\Repository\VideoRepository;
use App\Services\Contracts\ConfigServiceInterface;
use App\Services\Upload\DropboxUploadService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery;
use Mockery\MockInterface;
use Tests\DatabaseTestCase;

final class UploadVideoToDropboxStepTest extends DatabaseTestCase
{
    private UploadVideoToDropboxStep $step;

    private DropboxUploadService|MockInterface $uploadService;

    private ConfigServiceInterface|MockInterface $configService;

    private VideoRepository|MockInterface $videoRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadService = Mockery::mock(DropboxUploadService::class);
        $this->configService = Mockery::mock(ConfigServiceInterface::class);
        $this->videoRepository = Mockery::mock(VideoRepository::class);

        $this->step = new UploadVideoToDropboxStep(
            $this->uploadService,
            $this->configService,
            $this->videoRepository,
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
            IngestStepEnum::UploadVideoToDropbox,
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

    public function testItIsNotApplicableWhenDefaultFilesystemIsNotDropbox(): void
    {
        $context = $this->createContext();

        $this->configService
            ->shouldReceive('get')
            ->once()
            ->with(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'default', 'local')
            ->andReturn('local');

        $this->uploadService
            ->shouldNotReceive('exists');

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItIsApplicableWhenDefaultFilesystemIsDropboxAndFileDoesNotExistThere(): void
    {
        $context = $this->createContext(
            path: 'videos/test-video.mp4',
            isDuplicate: false,
        );

        $this->configService
            ->shouldReceive('get')
            ->once()
            ->with(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'default', 'local')
            ->andReturn('dropbox');

        $this->uploadService
            ->shouldReceive('exists')
            ->once()
            ->with('videos/test-video.mp4')
            ->andReturn(false);

        self::assertTrue($this->step->isApplicable($context));
    }

    public function testItIsNotApplicableWhenContextIsDuplicate(): void
    {
        $context = $this->createContext(
            path: 'videos/test-video.mp4',
            isDuplicate: true,
        );

        $this->configService
            ->shouldReceive('get')
            ->once()
            ->with(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'default', 'local')
            ->andReturn('dropbox');

        $this->uploadService
            ->shouldNotReceive('exists');

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItIsNotApplicableWhenFileAlreadyExistsInDropbox(): void
    {
        $context = $this->createContext(
            path: 'videos/test-video.mp4',
            isDuplicate: false,
        );

        $this->configService
            ->shouldReceive('get')
            ->once()
            ->with(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'default', 'local')
            ->andReturn('dropbox');

        $this->uploadService
            ->shouldReceive('exists')
            ->once()
            ->with('videos/test-video.mp4')
            ->andReturn(true);

        self::assertFalse($this->step->isApplicable($context));
    }

    public function testItReturnsContextUnchangedWhenDuplicate(): void
    {
        $context = $this->createContext(isDuplicate: true);

        $this->uploadService
            ->shouldNotReceive('uploadFile');

        $this->videoRepository
            ->shouldNotReceive('save');

        $result = $this->step->handle($context);

        self::assertSame($context, $result);
        self::assertTrue($result->isDuplicate);
    }

    public function testItUploadsVideoUpdatesDiskAndDeletesSourceWhenSaveSucceeds(): void
    {
        $sourceDisk = Mockery::mock(Filesystem::class);

        $context = $this->createContext(
            path: 'videos/test-video.mp4',
            disk: 'local',
            isDuplicate: false,
            sourceDisk: $sourceDisk,
        );

        $this->uploadService
            ->shouldReceive('uploadFile')
            ->once()
            ->withArgs(function (Filesystem $passedSourceDisk, string $relativePath, string $targetPath): bool {
                return $relativePath === 'videos/test-video.mp4'
                    && $targetPath === 'videos/test-video.mp4';
            });

        $this->videoRepository
            ->shouldReceive('save')
            ->once()
            ->with($context->video)
            ->andReturn(true);

        $sourceDisk
            ->shouldReceive('delete')
            ->once()
            ->with('videos/test-video.mp4');

        $result = $this->step->handle($context);

        self::assertSame($context, $result);
        self::assertSame('dropbox', $result->video->disk);
    }

    public function testItUploadsVideoUpdatesDiskAndDoesNotDeleteSourceWhenSaveFails(): void
    {
        $sourceDisk = Mockery::mock(Filesystem::class);

        $context = $this->createContext(
            path: 'videos/test-video.mp4',
            disk: 'local',
            isDuplicate: false,
            sourceDisk: $sourceDisk,
        );

        $this->uploadService
            ->shouldReceive('uploadFile')
            ->once()
            ->withArgs(function (Filesystem $passedSourceDisk, string $relativePath, string $targetPath): bool {
                return $relativePath === 'videos/test-video.mp4'
                    && $targetPath === 'videos/test-video.mp4';
            });

        $this->videoRepository
            ->shouldReceive('save')
            ->once()
            ->with($context->video)
            ->andReturn(false);

        $sourceDisk
            ->shouldNotReceive('delete');

        $result = $this->step->handle($context);

        self::assertSame($context, $result);
        self::assertSame('dropbox', $result->video->disk);
    }

    private function createContext(
        string $path = 'videos/default.mp4',
        string $disk = 'local',
        bool $isDuplicate = false,
        ?Filesystem $sourceDisk = null,
    ): IngestContext {
        $video = Mockery::mock(Video::class)->makePartial();
        $video->path = $path;
        $video->disk = $disk;
        $video->clips = collect();

        $sourceDisk ??= Mockery::mock(Filesystem::class);

        $video
            ->shouldReceive('getDisk')
            ->andReturn($sourceDisk);

        return new IngestContext(
            video: $video,
            isDuplicate: $isDuplicate,
        );
    }
}
