<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Services\CleanupService;
use Illuminate\Console\Command;
use Tests\TestCase;

final class CleanUpDiskCommandTest extends TestCase
{
    public function testUsesDefaultDiskAndDaysWhenOptionsOmitted(): void
    {
        $this->mock(CleanupService::class)
            ->shouldReceive('cleanDisk')
            ->once()
            ->with('uploads', 30)
            ->andReturn(0);

        $this->artisan('clean:disk')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testPassesCustomOptionsToCleanupService(): void
    {
        $this->mock(CleanupService::class)
            ->shouldReceive('cleanDisk')
            ->once()
            ->with('backups', 7)
            ->andReturn(5);

        $this->artisan('clean:disk --disk=backups --days=7')
            ->assertExitCode(Command::SUCCESS);
    }
}
