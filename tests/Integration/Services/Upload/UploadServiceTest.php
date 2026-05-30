<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Upload;

use App\Services\Upload\DropboxUploadService;
use App\Services\Upload\UploadService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class UploadServiceTest extends TestCase
{
    private UploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(UploadService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUploadFileToNonDropboxDiskWritesContentToTargetDisk(): void
    {
        Storage::fake('source');
        Storage::fake('target');
        Storage::disk('source')->put('video.mp4', 'content');

        $this->service->uploadFile(
            Storage::disk('source'),
            'video.mp4',
            'target',
            'uploads/video.mp4'
        );

        Storage::disk('target')->assertExists('uploads/video.mp4');
    }

    public function testUploadFilePreservesContentOnTargetDisk(): void
    {
        Storage::fake('source');
        Storage::fake('archive');
        Storage::disk('source')->put('clip.mp4', 'binary-data');

        $this->service->uploadFile(
            Storage::disk('source'),
            'clip.mp4',
            'archive',
            'clips/clip.mp4'
        );

        $this->assertSame('binary-data', Storage::disk('archive')->get('clips/clip.mp4'));
    }

    public function testUploadFileToDropboxDelegatesToDropboxUploadService(): void
    {
        Storage::fake('source');
        Storage::disk('source')->put('clip.mp4', 'data');

        $dropboxService = Mockery::mock(DropboxUploadService::class);
        $dropboxService->shouldReceive('uploadFile')
            ->once()
            ->with(Mockery::type(Filesystem::class), 'clip.mp4', 'remote/clip.mp4');

        $this->app->instance(DropboxUploadService::class, $dropboxService);

        $this->service->uploadFile(
            Storage::disk('source'),
            'clip.mp4',
            'dropbox',
            'remote/clip.mp4'
        );

        $this->addToAssertionCount(1);
    }

    public function testUploadToDropboxDelegatesToDropboxUploadService(): void
    {
        Storage::fake('source');
        Storage::disk('source')->put('file.mp4', 'data');

        $dropboxService = Mockery::mock(DropboxUploadService::class);
        $dropboxService->shouldReceive('uploadFile')
            ->once()
            ->with(Mockery::type(Filesystem::class), 'file.mp4', 'dest/file.mp4');

        $this->app->instance(DropboxUploadService::class, $dropboxService);

        $this->service->uploadToDropbox(
            Storage::disk('source'),
            'file.mp4',
            'dest/file.mp4'
        );

        $this->addToAssertionCount(1);
    }
}
