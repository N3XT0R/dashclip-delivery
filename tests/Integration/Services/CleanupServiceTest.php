<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\CleanupService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CleanupServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/dashclip_cleanup_' . uniqid();
        mkdir($this->tempDir);

        config(['filesystems.disks.test_cleanup' => [
            'driver' => 'local',
            'root' => $this->tempDir,
        ]]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (glob($this->tempDir . '/*') as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
    }

    public function testDeletesOldFilesAndReturnsCount(): void
    {
        $disk = Storage::disk('test_cleanup');
        $disk->put('old.mp4', 'old content');
        $disk->put('recent.mp4', 'recent content');

        touch($this->tempDir . '/old.mp4', time() - (10 * 86400));

        $service = $this->app->make(CleanupService::class);
        $deleted = $service->cleanDisk('test_cleanup', days: 5);

        $this->assertSame(1, $deleted);
        $this->assertFalse($disk->exists('old.mp4'));
        $this->assertTrue($disk->exists('recent.mp4'));
    }

    public function testReturnsZeroWhenNoFilesAreOldEnough(): void
    {
        $disk = Storage::disk('test_cleanup');
        $disk->put('new.mp4', 'content');

        $service = $this->app->make(CleanupService::class);
        $deleted = $service->cleanDisk('test_cleanup', days: 30);

        $this->assertSame(0, $deleted);
        $this->assertTrue($disk->exists('new.mp4'));
    }
}
