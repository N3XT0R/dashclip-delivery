<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class CleanFfmpegTmpCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = storage_path('app/ffmpeg-tmp');

        if (is_dir($this->tmpDir)) {
            File::deleteDirectory($this->tmpDir);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            File::deleteDirectory($this->tmpDir);
        }
        parent::tearDown();
    }

    public function testReturnsSuccessWhenDirectoryDoesNotExist(): void
    {
        $this->artisan('clean:ffmpeg-tmp')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testDeletesFilesOlderThanDefaultCutoff(): void
    {
        mkdir($this->tmpDir, 0777, true);
        $staleFile = $this->tmpDir . '/stale.tmp';
        file_put_contents($staleFile, 'data');
        touch($staleFile, time() - 3 * 3600); // 3 hours old, default cutoff is 2

        $this->artisan('clean:ffmpeg-tmp')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileDoesNotExist($staleFile);
    }

    public function testKeepsFilesNewerThanCutoff(): void
    {
        mkdir($this->tmpDir, 0777, true);
        $freshFile = $this->tmpDir . '/fresh.tmp';
        file_put_contents($freshFile, 'data');
        touch($freshFile, time() - 1 * 3600); // 1 hour old, default cutoff is 2

        $this->artisan('clean:ffmpeg-tmp')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($freshFile);
    }

    public function testCustomOlderThanOptionAdjustsCutoff(): void
    {
        mkdir($this->tmpDir, 0777, true);

        $staleFile = $this->tmpDir . '/old.tmp';
        file_put_contents($staleFile, 'data');
        touch($staleFile, time() - 5 * 3600); // 5 hours old

        $freshFile = $this->tmpDir . '/new.tmp';
        file_put_contents($freshFile, 'data');
        touch($freshFile, time() - 3 * 3600); // 3 hours old

        $this->artisan('clean:ffmpeg-tmp', ['--older-than' => 4])
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileDoesNotExist($staleFile);
        $this->assertFileExists($freshFile);
    }

    public function testReturnsSuccessForEmptyDirectory(): void
    {
        mkdir($this->tmpDir, 0777, true);

        $this->artisan('clean:ffmpeg-tmp')
            ->assertExitCode(Command::SUCCESS);
    }
}
