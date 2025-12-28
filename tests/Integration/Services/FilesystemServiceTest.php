<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\FilesystemService;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\DatabaseTestCase;

final class FilesystemServiceTest extends DatabaseTestCase
{
    private FilesystemService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(FilesystemService::class);
    }

    public function testGetDiskPathReturnsConfiguredRoot(): void
    {
        config()->set('filesystems.disks.testdisk', [
            'driver' => 'local',
            'root' => '/tmp/testdisk',
        ]);

        $path = $this->service->getDiskPath('testdisk');

        $this->assertSame('/tmp/testdisk', $path);
    }

    public function testGetDiskPathThrowsExceptionForUnknownDisk(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->getDiskPath('unknown_disk');
    }

    public function testGetFilesFromDiskReturnsAllFiles(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('a.txt', 'a');
        Storage::disk('local')->put('dir/b.txt', 'b');

        $files = $this->service->getFilesFromDisk(
            Storage::disk('local')
        );

        sort($files);

        $this->assertSame(
            ['a.txt', 'dir/b.txt'],
            $files
        );
    }

    public function testGetFilesOlderThanReturnsOnlyOldFiles(): void
    {
        Storage::fake('local');

        $disk = Storage::disk('local');

        $disk->put('old.txt', 'old');
        $disk->put('new.txt', 'new');

        $oldTimestamp = now()->subDays(10)->timestamp;
        $newTimestamp = now()->subDay()->timestamp;

        touch($disk->path('old.txt'), $oldTimestamp);
        touch($disk->path('new.txt'), $newTimestamp);

        $files = $this->service->getFilesOlderThan($disk, 5);

        $this->assertSame(['old.txt'], $files);
    }

    public function testDeleteFilesDeletesFilesAndReturnsCount(): void
    {
        Storage::fake('local');

        $disk = Storage::disk('local');

        $disk->put('a.txt', 'a');
        $disk->put('b.txt', 'b');

        $deleted = $this->service->deleteFiles(
            $disk,
            ['a.txt', 'b.txt', 'missing.txt']
        );

        $this->assertSame(3, $deleted);
        $this->assertFalse($disk->exists('a.txt'));
        $this->assertFalse($disk->exists('b.txt'));
    }
}
