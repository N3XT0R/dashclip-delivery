<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\CleanupService;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class CleanupServiceTest extends DatabaseTestCase
{
    private CleanupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(CleanupService::class);
    }

    public function testCleanDiskDeletesOnlyFilesOlderThanGivenDays(): void
    {
        Storage::fake('local');

        $disk = Storage::disk('local');

        $disk->put('old.txt', 'old');
        $disk->put('new.txt', 'new');

        $oldTimestamp = now()->subDays(10)->timestamp;
        $newTimestamp = now()->subDay()->timestamp;

        touch($disk->path('old.txt'), $oldTimestamp);
        touch($disk->path('new.txt'), $newTimestamp);

        $deleted = $this->service->cleanDisk('local', 5);

        $this->assertSame(1, $deleted);
        $this->assertFalse($disk->exists('old.txt'));
        $this->assertTrue($disk->exists('new.txt'));
    }

    public function testCleanDiskReturnsZeroWhenNoFilesAreOldEnough(): void
    {
        Storage::fake('local');

        $disk = Storage::disk('local');

        $disk->put('recent.txt', 'recent');

        touch(
            $disk->path('recent.txt'),
            now()->subDay()->timestamp
        );

        $deleted = $this->service->cleanDisk('local', 5);

        $this->assertSame(0, $deleted);
        $this->assertTrue($disk->exists('recent.txt'));
    }

    public function testCleanDiskDeletesAllFilesWhenAllAreOld(): void
    {
        Storage::fake('local');

        $disk = Storage::disk('local');

        $disk->put('a.txt', 'a');
        $disk->put('b.txt', 'b');

        $timestamp = now()->subDays(10)->timestamp;

        touch($disk->path('a.txt'), $timestamp);
        touch($disk->path('b.txt'), $timestamp);

        $deleted = $this->service->cleanDisk('local', 5);

        $this->assertSame(2, $deleted);
        $this->assertFalse($disk->exists('a.txt'));
        $this->assertFalse($disk->exists('b.txt'));
    }
}
