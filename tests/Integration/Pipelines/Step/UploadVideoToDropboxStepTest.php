<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines\Step;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\Ingest\IngestStepEnum;
use App\Exceptions\Storage\VideoStorageMigrationException;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Pipelines\Ingest\Step\UploadVideoToDropboxStep;
use App\Repository\VideoRepository;
use App\Services\Contracts\ConfigServiceInterface;
use App\Services\Storage\StorageDiskService;
use App\Services\Upload\UploadService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\DatabaseTestCase;

/**
 * The step moves new videos to the storage disk set in default_file_system.
 */
final class UploadVideoToDropboxStepTest extends DatabaseTestCase
{
    private UploadVideoToDropboxStep $step;

    private UploadService|MockInterface $uploadService;

    private ConfigServiceInterface|MockInterface $configService;

    private VideoRepository|MockInterface $videoRepository;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/ingest-target-'.Str::uuid());
        Storage::extend('remote-test', static fn ($app, array $config) => Storage::createLocalDriver($config));
        foreach (['dropbox', 'hetzner'] as $disk) {
            config()->set("filesystems.disks.$disk", ['driver' => 'remote-test', 'root' => $this->root.'/'.$disk]);
            Storage::forgetDisk($disk);
        }

        $this->uploadService = Mockery::mock(UploadService::class);
        $this->configService = Mockery::mock(ConfigServiceInterface::class);
        $this->videoRepository = Mockery::mock(VideoRepository::class);

        $this->step = new UploadVideoToDropboxStep(
            $this->uploadService,
            $this->configService,
            $this->videoRepository,
            new StorageDiskService(),
        );
    }

    protected function tearDown(): void
    {
        Storage::build(['driver' => 'local', 'root' => $this->root])->deleteDirectory('');
        Mockery::close();

        parent::tearDown();
    }

    private function target(string $disk): void
    {
        $this->configService
            ->shouldReceive('get')
            ->with(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'default', 'local')
            ->andReturn($disk);
    }

    public function testItKeepsItsStepNameAndDependencies(): void
    {
        self::assertSame(IngestStepEnum::UploadVideoToDropbox, $this->step->name());
        self::assertSame([IngestStepEnum::LookupAndUpdateVideoHash], $this->step->dependsOn());
    }

    public function testItIsApplicableForAnyConfiguredRemoteTarget(): void
    {
        foreach (['dropbox', 'hetzner'] as $disk) {
            $this->configService = Mockery::mock(ConfigServiceInterface::class);
            $step = new UploadVideoToDropboxStep($this->uploadService, $this->configService, $this->videoRepository, new StorageDiskService());
            $this->target($disk);

            self::assertTrue($step->isApplicable($this->createContext()), $disk.' is a valid target.');
        }
    }

    public function testLocalOrUnknownTargetsLeaveTheVideoWhereItIs(): void
    {
        foreach (['local', 'videos', 'unknown'] as $disk) {
            $this->configService = Mockery::mock(ConfigServiceInterface::class);
            $step = new UploadVideoToDropboxStep($this->uploadService, $this->configService, $this->videoRepository, new StorageDiskService());
            $this->target($disk);

            self::assertFalse($step->isApplicable($this->createContext()), $disk.' must not move the video.');
        }
    }

    public function testItSkipsDuplicatesAndInvalidFilesButRetriesExistingTargetCopies(): void
    {
        $this->target('hetzner');

        self::assertFalse($this->step->isApplicable($this->createContext(isDuplicate: true)));
        self::assertFalse($this->step->isApplicable($this->createContext(isInvalid: true)));
        self::assertFalse($this->step->isApplicable($this->createContext(disk: 'hetzner')));

        Storage::disk('hetzner')->put('videos/test-video.mp4', 'content');
        self::assertTrue($this->step->isApplicable($this->createContext()));
    }

    public function testItReturnsContextUnchangedWhenDuplicate(): void
    {
        $context = $this->createContext(isDuplicate: true);
        $this->uploadService->shouldNotReceive('uploadFile');

        self::assertSame($context, $this->step->handle($context));
    }

    public function testItMovesTheVideoToTheTargetAndDeletesTheSourceWhenSaved(): void
    {
        $this->target('hetzner');
        $sourceDisk = Mockery::mock(Filesystem::class);
        $context = $this->createContext(sourceDisk: $sourceDisk);
        $sourceDisk->shouldReceive('exists')->with('videos/test-video.mp4')->andReturn(true);
        $sourceDisk->shouldReceive('size')->with('videos/test-video.mp4')->andReturn(7);
        Storage::disk('hetzner')->put('videos/test-video.mp4', 'content');

        $this->uploadService->shouldReceive('uploadFile')->once()
            ->with(Mockery::type(Filesystem::class), 'videos/test-video.mp4', 'hetzner', 'videos/test-video.mp4');
        $this->videoRepository->shouldReceive('save')->once()->with($context->video)->andReturn(true);
        $sourceDisk->shouldReceive('delete')->once()->with('videos/test-video.mp4');

        $result = $this->step->handle($context);

        self::assertSame('hetzner', $result->video->disk);
    }

    public function testItKeepsTheSourceWhenSavingFails(): void
    {
        $this->target('dropbox');
        $sourceDisk = Mockery::mock(Filesystem::class);
        $context = $this->createContext(sourceDisk: $sourceDisk);
        $sourceDisk->shouldReceive('exists')->with('videos/test-video.mp4')->andReturn(true);
        $sourceDisk->shouldReceive('size')->with('videos/test-video.mp4')->andReturn(7);
        Storage::disk('dropbox')->put('videos/test-video.mp4', 'content');

        $this->uploadService->shouldReceive('uploadFile')->once()
            ->with(Mockery::type(Filesystem::class), 'videos/test-video.mp4', 'dropbox', 'videos/test-video.mp4');
        $this->videoRepository->shouldReceive('save')->once()->andReturn(false);
        $sourceDisk->shouldNotReceive('delete');

        self::assertSame('dropbox', $this->step->handle($context)->video->disk);
    }

    public function testBothVersionsMoveTogetherUsingRealStorage(): void
    {
        $context = $this->realVideoContext();
        $step = $this->app->make(UploadVideoToDropboxStep::class);

        $step->handle($context);

        self::assertSame('hetzner', $context->video->refresh()->disk);
        self::assertSame('blurred', Storage::disk('hetzner')->get('blurred.mp4'));
        self::assertSame('original', Storage::disk('hetzner')->get('original.mp4'));
        self::assertFalse(Storage::disk('videos')->exists('blurred.mp4'));
        self::assertFalse(Storage::disk('videos')->exists('original.mp4'));
        $step->handle($context);
        self::assertSame('original', Storage::disk('hetzner')->get('original.mp4'));
    }

    public function testMissingOriginalKeepsSourceFilesAndRetryCompletesBothCopies(): void
    {
        $context = $this->realVideoContext();
        Storage::disk('videos')->delete('original.mp4');
        $step = $this->app->make(UploadVideoToDropboxStep::class);

        try {
            $step->handle($context);
            self::fail('A missing original must prevent switching storage.');
        } catch (VideoStorageMigrationException) {
            self::assertSame('videos', $context->video->refresh()->disk);
            self::assertTrue(Storage::disk('videos')->exists('blurred.mp4'));
        }

        Storage::disk('videos')->put('original.mp4', 'original');
        self::assertTrue($step->isApplicable($context));
        $step->handle($context);

        self::assertSame('hetzner', $context->video->refresh()->disk);
        self::assertSame('original', Storage::disk('hetzner')->get('original.mp4'));
    }

    public function testFailedOriginalUploadKeepsBothSourceFiles(): void
    {
        $context = $this->realVideoContext();
        Storage::disk('hetzner')->put('original.mp4/blocker', 'directory blocks file writes');
        $step = $this->app->make(UploadVideoToDropboxStep::class);

        try {
            $step->handle($context);
            self::fail('A failed original upload must prevent switching.');
        } catch (VideoStorageMigrationException) {
            self::assertSame('videos', $context->video->refresh()->disk);
            self::assertSame('original', Storage::disk('videos')->get('original.mp4'));
            self::assertSame('blurred', Storage::disk('videos')->get('blurred.mp4'));
        }
    }

    private function realVideoContext(): IngestContext
    {
        config()->set('filesystems.disks.videos', ['driver' => 'local', 'root' => $this->root.'/videos']);
        Storage::forgetDisk('videos');
        $this->app->make(ConfigServiceInterface::class)->set(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'hetzner');
        Storage::disk('videos')->put('blurred.mp4', 'blurred');
        Storage::disk('videos')->put('original.mp4', 'original');

        return new IngestContext(Video::factory()->create([
            'disk' => 'videos', 'path' => 'blurred.mp4', 'source_path' => 'original.mp4',
        ]));
    }

    private function createContext(
        string $path = 'videos/test-video.mp4',
        string $disk = 'videos',
        bool $isDuplicate = false,
        bool $isInvalid = false,
        ?Filesystem $sourceDisk = null,
    ): IngestContext {
        $video = Mockery::mock(Video::class)->makePartial();
        $video->path = $path;
        $video->disk = $disk;
        $video->clips = collect();

        $sourceDisk ??= Mockery::mock(Filesystem::class);
        $video->shouldReceive('getDisk')->andReturn($sourceDisk);

        return new IngestContext(video: $video, isDuplicate: $isDuplicate, isInvalid: $isInvalid);
    }
}
