<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\DatabaseTestCase;

class ScanMailRepliesCommandTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    public function testItInvokesScannerWhenFaqEmailIsConfigured(): void
    {
        $this->artisan('mail:scan-replies')
            ->assertExitCode(Command::SUCCESS);
    }
}
