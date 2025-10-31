<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Batch;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Storage;
use Tests\DatabaseTestCase;

/**
 * Feature tests for the "ingest:scan" console command with the real IngestScanner.
 *
 * - No mocking/faking of services; we only point ffmpeg to a tiny shell script that "succeeds".
 * - We create real files under storage_path('app/...') so the default 'local' disk resolves correctly.
 * - We assert DB side-effects (ingest batch, video rows) and on-disk outcomes (files moved).
 */
final class IngestScanTest extends DatabaseTestCase
{

    /** Happy path: one new video is ingested to the local disk; batch stats and file move are correct. */
    public function testCommandMovesVideoAndCreatesBatchStats(): void
    {
        // Arrange
        Storage::fake('local');

        // Use fixture videos (identical content)
        $inboxPath = base_path('tests/Fixtures/Inbox/Videos');
        $inboxDisk = app('filesystem')->build([
            'driver' => 'local',
            'root' => $inboxPath,
        ]);

        // Copy fixtures to a temporary fake inbox
        $tmpDisk = Storage::fake('tmp');
        $tmpDisk->deleteDirectory('');
        $tmpDisk->makeDirectory('');
        $this->copyDisk($inboxDisk, $tmpDisk);

        $inboxAbs = $tmpDisk->path('');

        // Sanity: DB should be empty
        $this->assertDatabaseCount('videos', 0);
        $this->assertNull(
            Batch::query()->where('type', 'ingest')->latest('id')->first(),
            'Expected no existing ingest batch before test run'
        );

        // Act
        $this->artisan('ingest:scan', [
            '--inbox' => $inboxAbs,
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('Starte Scan:')
            ->expectsOutputToContain('Fertig.')
            ->assertExitCode(Command::SUCCESS);

        // Assert: a new ingest batch exists with valid stats
        $batch = Batch::query()
            ->where('type', 'ingest')
            ->latest('id')
            ->first();

        $this->assertNotNull($batch, 'Expected an ingest batch to be created');
        $this->assertNotNull($batch->started_at);
        $this->assertNotNull($batch->finished_at);
        $this->assertIsArray($batch->stats);
        $this->assertArrayHasKey('new', $batch->stats);
        $this->assertArrayHasKey('dups', $batch->stats);
        $this->assertArrayHasKey('err', $batch->stats);
        $this->assertSame(1, $batch->stats['new'], 'Expected one new video');
        $this->assertSame(0, $batch->stats['err'], 'Expected zero errors');

        // Assert: one video row created and moved to content-addressed path on the local disk
        $video = Video::query()->latest('id')->first();
        $this->assertNotNull($video, 'Expected a video record to exist');
        $this->assertSame('local', $video->disk);
        $this->assertNotEmpty($video->hash);
        $this->assertSame('mp4', $video->ext);
        $this->assertStringEndsWith('.mp4', $video->original_name);

        // The destination path should follow the hashed structure: videos/xx/xx/<hash>.mp4
        $this->assertMatchesRegularExpression(
            '#^videos/[0-9a-f]{2}/[0-9a-f]{2}/[0-9a-f]{64}\.mp4$#',
            $video->path
        );


        // Assert: destination file exists on local disk and has content
        $destAbs = app('filesystem')->disk('local')->path($video->path);
        $this->assertFileExists($destAbs);
        $this->assertGreaterThan(0, filesize($destAbs) ?: 0);
    }


    public function testReturnsSuccessWhenAnotherIngestIsRunning(): void
    {
        Batch::query()->delete();

        // Arrange: simulate another ingest in progress via cache lock
        $lock = cache()->lock('ingest:lock', 40);
        $this->assertTrue($lock->get(), 'Unable to acquire initial test lock');

        // Unique temporary inbox path
        $inbox = storage_path('app/inbox_'.bin2hex(random_bytes(4)));

        // Act: run the command while the lock is held
        $this->artisan('ingest:scan', [
            '--inbox' => $inbox,
            '--disk' => 'local',
        ])
            ->expectsOutput('Another ingest task is running. Abort.')
            ->assertExitCode(Command::SUCCESS);

        // Cleanup
        $lock->release();

        // Assert: no batch should have been created
        $this->assertDatabaseCount('batches', 0);
    }

    public function testCommandProcessesInboxAndCountsDuplicates(): void
    {
        // Arrange
        Storage::fake('local');

        // Use fixture videos (identical content)
        $inboxPath = base_path('tests/Fixtures/Inbox/Videos');
        $inboxDisk = app('filesystem')->build([
            'driver' => 'local',
            'root' => $inboxPath,
        ]);

        // Copy fixtures to a temporary fake inbox
        $tmpDisk = Storage::fake('tmp');
        $tmpDisk->deleteDirectory('');
        $tmpDisk->makeDirectory('');
        $this->copyDisk($inboxDisk, $tmpDisk);

        $inboxAbs = $tmpDisk->path('');

        // Act: run the artisan command
        $this->artisan('ingest:scan', [
            '--inbox' => $inboxAbs,
            '--disk' => 'local',
        ])
            ->expectsOutputToContain('Starte Scan:')
            ->expectsOutputToContain('Fertig.')
            ->assertExitCode(Command::SUCCESS);

        // Assert: one batch created with proper stats
        $batch = Batch::query()
            ->where('type', 'ingest')
            ->latest('id')
            ->first();

        $this->assertNotNull($batch, 'No batch record was created');
        $this->assertSame(1, $batch->stats['new'], 'Expected 1 new video');
        $this->assertSame(2, $batch->stats['dups'], 'Expected 2 duplicates');
        $this->assertSame(0, $batch->stats['err'], 'Expected 0 errors');

        // Assert: only one unique video stored in DB
        $this->assertDatabaseCount('videos', 1);
        $video = Video::first();
        $this->assertNotNull($video);
        $this->assertNotEmpty($video->hash);
        $this->assertSame('local', $video->disk);
    }

    /**
     * Helper to recursively copy all files & dirs from one disk to another.
     */
    private function copyDisk(Filesystem $source, Filesystem $target): void
    {
        foreach ($source->allFiles() as $path) {
            $target->put($path, $source->get($path));
        }

        foreach ($source->allDirectories() as $dir) {
            $target->makeDirectory($dir);
        }
    }

    /** Error path: non-existent inbox should produce FAILURE and print the error message. */
    public function testFailsWhenInboxDoesNotExist(): void
    {
        $missing = sys_get_temp_dir().'/missing_root_'.bin2hex(random_bytes(4)).'/nested/';
        $this->artisan('ingest:scan', [
            '--inbox' => $missing,
            '--disk' => 'local'
        ])
            ->expectsOutputToContain('Inbox fehlt:')
            ->assertExitCode(Command::FAILURE);
    }
}
