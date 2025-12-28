<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class CleanUpDiskCommandTest extends TestCase
{
    public function testUsesDefaultDiskAndDaysWhenOptionsOmitted(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2024-02-01 12:00:00'));

        $disk = Storage::fake('uploads');
        $disk->put('old/cleanup.txt', 'old');
        $disk->put('recent/keep.txt', 'recent');

        touch($disk->path('old/cleanup.txt'), $now->copy()->subDays(31)->timestamp);
        touch($disk->path('recent/keep.txt'), $now->copy()->subDays(10)->timestamp);

        $this->artisan('clean:disk')
            ->assertExitCode(Command::SUCCESS);

        $disk->assertMissing('old/cleanup.txt');
        $disk->assertExists('recent/keep.txt');

        Carbon::setTestNow();
    }

    public function testPassesCustomOptionsToCleanupService(): void
    {
        Carbon::setTestNow($now = Carbon::parse('2024-02-01 12:00:00'));

        $disk = Storage::fake('backups');
        $disk->put('very-old.txt', 'remove');
        $disk->put('fresh.txt', 'keep');

        touch($disk->path('very-old.txt'), $now->copy()->subDays(8)->timestamp);
        touch($disk->path('fresh.txt'), $now->copy()->subDays(2)->timestamp);

        $this->artisan('clean:disk --disk=backups --days=7')
            ->assertExitCode(Command::SUCCESS);

        $disk->assertMissing('very-old.txt');
        $disk->assertExists('fresh.txt');

        Carbon::setTestNow();
    }
}
