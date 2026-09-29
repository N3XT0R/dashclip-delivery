<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Storage;

use App\Enum\VideoStorageMigrationResultEnum;
use App\Exceptions\Storage\VideoStorageMigrationException;
use App\Models\Video;
use App\Services\Storage\VideoStorageMigrationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

final class VideoStorageMigrationServiceTest extends DatabaseTestCase
{
    private string $root;

    private VideoStorageMigrationService $migration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/storage-migration-'.Str::uuid());
        foreach (['dropbox', 'hetzner'] as $disk) {
            config()->set("filesystems.disks.$disk", ['driver' => 'local', 'root' => $this->root.'/'.$disk, 'throw' => false]);
            Storage::forgetDisk($disk);
        }
        $this->migration = $this->app->make(VideoStorageMigrationService::class);
    }

    protected function tearDown(): void
    {
        Storage::build(['driver' => 'local', 'root' => $this->root])->deleteDirectory('');
        parent::tearDown();
    }

    private function video(string $content = 'video-content'): Video
    {
        $path = 'clips/'.Str::uuid().'.mp4';
        Storage::disk('dropbox')->put($path, $content);

        return Video::factory()->create(['disk' => 'dropbox', 'path' => $path, 'bytes' => strlen($content)]);
    }

    public function testVideoIsCopiedAndSwitchedToTheTarget(): void
    {
        $video = $this->video();

        $result = $this->migration->migrate($video, 'hetzner');

        $this->assertSame(VideoStorageMigrationResultEnum::Copied, $result);
        $this->assertSame('video-content', Storage::disk('hetzner')->get($video->path));
        $this->assertSame('hetzner', $video->refresh()->disk);
        $this->assertTrue(Storage::disk('dropbox')->exists($video->path), 'The source copy stays untouched.');
    }

    public function testCompleteCopyAtTheTargetIsOnlySwitched(): void
    {
        $video = $this->video();
        Storage::disk('hetzner')->put($video->path, 'VIDEO-CONTENT');

        $result = $this->migration->migrate($video, 'hetzner');

        $this->assertSame(VideoStorageMigrationResultEnum::Switched, $result);
        $this->assertSame('VIDEO-CONTENT', Storage::disk('hetzner')->get($video->path), 'An equally sized copy is reused.');
        $this->assertSame('hetzner', $video->refresh()->disk);
    }

    public function testIncompleteCopyAtTheTargetIsReplaced(): void
    {
        $video = $this->video();
        Storage::disk('hetzner')->put($video->path, 'video');

        $this->assertSame(VideoStorageMigrationResultEnum::Copied, $this->migration->migrate($video, 'hetzner'));
        $this->assertSame('video-content', Storage::disk('hetzner')->get($video->path));
    }

    public function testDryRunChangesNothing(): void
    {
        $video = $this->video();

        $this->assertSame(VideoStorageMigrationResultEnum::WouldCopy, $this->migration->migrate($video, 'hetzner', dryRun: true));
        $this->assertFalse(Storage::disk('hetzner')->exists($video->path));
        $this->assertSame('dropbox', $video->refresh()->disk);
    }

    public function testMissingSourceIsReportedWithoutSwitching(): void
    {
        $video = $this->video();
        Storage::disk('dropbox')->delete($video->path);

        try {
            $this->migration->migrate($video, 'hetzner');
            $this->fail('A missing source must not be migrated.');
        } catch (VideoStorageMigrationException $exception) {
            $this->assertStringContainsString((string) $video->id, $exception->getMessage());
        }
        $this->assertSame('dropbox', $video->refresh()->disk);
    }

    public function testBothVersionsAreCopiedBeforeSwitching(): void
    {
        $video = $this->video();
        $video->update(['source_path' => 'original.mp4']);
        Storage::disk('dropbox')->put('original.mp4', 'original-content');
        Storage::disk('hetzner')->put($video->path, 'video-content');

        self::assertSame(VideoStorageMigrationResultEnum::WouldCopy, $this->migration->migrate($video, 'hetzner', true));
        self::assertFalse(Storage::disk('hetzner')->exists('original.mp4'));
        self::assertSame('dropbox', $video->refresh()->disk);
        self::assertSame(VideoStorageMigrationResultEnum::Copied, $this->migration->migrate($video, 'hetzner'));
        self::assertSame('original-content', Storage::disk('hetzner')->get('original.mp4'));
        self::assertSame('video-content', Storage::disk('hetzner')->get($video->path));
        self::assertSame('hetzner', $video->refresh()->disk);
        self::assertTrue(Storage::disk('dropbox')->exists('original.mp4'));
    }

    public function testFailedOriginalCopyLeavesTheVideoOnItsSourceDisk(): void
    {
        $video = $this->video();
        $video->update(['source_path' => 'original.mp4']);
        Storage::disk('dropbox')->put('original.mp4', 'original-content');
        Storage::disk('hetzner')->put('original.mp4/blocker', 'directory blocks file writes');

        try {
            $this->migration->migrate($video, 'hetzner');
            self::fail('A failed original copy must prevent switching.');
        } catch (VideoStorageMigrationException) {
            self::assertSame('dropbox', $video->refresh()->disk);
            self::assertSame('original-content', Storage::disk('dropbox')->get('original.mp4'));
            self::assertTrue(Storage::disk('dropbox')->exists($video->path));
        }
    }

    public function testMissingOriginalPreventsSwitchingAndCanBeRetried(): void
    {
        $video = $this->video();
        $video->update(['source_path' => 'original.mp4']);

        try {
            $this->migration->migrate($video, 'hetzner');
            self::fail('Both versions must exist before switching.');
        } catch (VideoStorageMigrationException) {
            self::assertSame('dropbox', $video->refresh()->disk);
            self::assertTrue(Storage::disk('dropbox')->exists($video->path));
        }

        Storage::disk('dropbox')->put('original.mp4', 'original-content');
        self::assertSame(VideoStorageMigrationResultEnum::Copied, $this->migration->migrate($video, 'hetzner'));
        self::assertSame('original-content', Storage::disk('hetzner')->get('original.mp4'));
        self::assertSame('hetzner', $video->refresh()->disk);
    }
}
