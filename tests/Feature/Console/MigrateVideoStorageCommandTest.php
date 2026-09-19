<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\DatabaseTestCase;

final class MigrateVideoStorageCommandTest extends DatabaseTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/storage-command-'.Str::uuid());
        foreach (['dropbox', 'hetzner', 'uploads'] as $disk) {
            config()->set("filesystems.disks.$disk", ['driver' => 'local', 'root' => $this->root.'/'.$disk, 'throw' => false]);
            Storage::forgetDisk($disk);
        }
    }

    protected function tearDown(): void
    {
        Storage::build(['driver' => 'local', 'root' => $this->root])->deleteDirectory('');
        parent::tearDown();
    }

    private function video(string $disk = 'dropbox', bool $withFile = true): Video
    {
        $path = 'clips/'.Str::uuid().'.mp4';
        if ($withFile) {
            Storage::disk($disk)->put($path, 'content');
        }

        return Video::factory()->create(['disk' => $disk, 'path' => $path, 'bytes' => 7]);
    }

    public function testAllVideosOfTheSourceDiskAreMigrated(): void
    {
        $first = $this->video();
        $second = $this->video();
        $elsewhere = $this->video('uploads');

        $this->artisan('storage:migrate-videos', ['from' => 'dropbox', 'to' => 'hetzner'])
            ->expectsOutputToContain('2 copied, 0 switched, 0 failed')
            ->assertSuccessful();

        $this->assertSame(['hetzner', 'hetzner', 'uploads'], [$first->refresh()->disk, $second->refresh()->disk, $elsewhere->refresh()->disk]);
    }

    public function testDryRunChangesNothing(): void
    {
        $video = $this->video();

        $this->artisan('storage:migrate-videos', ['from' => 'dropbox', 'to' => 'hetzner', '--dry-run' => true])
            ->expectsOutputToContain('1 would be copied')
            ->assertSuccessful();

        $this->assertSame('dropbox', $video->refresh()->disk);
        $this->assertFalse(Storage::disk('hetzner')->exists($video->path));
    }

    public function testVideoAndLimitOptionsNarrowTheSelection(): void
    {
        $first = $this->video();
        $second = $this->video();
        $third = $this->video();

        $this->artisan('storage:migrate-videos', ['from' => 'dropbox', 'to' => 'hetzner', '--video' => [$third->id]])->assertSuccessful();
        $this->artisan('storage:migrate-videos', ['from' => 'dropbox', 'to' => 'hetzner', '--limit' => 1])->assertSuccessful();

        $this->assertSame(['hetzner', 'dropbox', 'hetzner'], [$first->refresh()->disk, $second->refresh()->disk, $third->refresh()->disk]);
    }

    public function testFailingVideoIsLoggedAndTheOthersContinue(): void
    {
        Log::spy();
        $broken = $this->video(withFile: false);
        $good = $this->video();

        $this->artisan('storage:migrate-videos', ['from' => 'dropbox', 'to' => 'hetzner'])
            ->expectsOutputToContain('1 copied, 0 switched, 1 failed')
            ->assertFailed();

        $this->assertSame(['dropbox', 'hetzner'], [$broken->refresh()->disk, $good->refresh()->disk]);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context = []): bool => $message === 'Video storage migration failed' && $context['video_id'] === $broken->id,
        )->once();
    }

    public function testInvalidDisksAreRejected(): void
    {
        $this->artisan('storage:migrate-videos', ['from' => 'dropbox', 'to' => 'dropbox'])->assertFailed();
        $this->artisan('storage:migrate-videos', ['from' => 'dropbox', 'to' => 'unknown'])->assertFailed();
    }
}
