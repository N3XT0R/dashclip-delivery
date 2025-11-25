<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use App\Services\Mail\Scanner\MailReplyScanner;
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
        $scanner = Mockery::mock(MailReplyScanner::class);
        $scanner->shouldReceive('scan')->once();

        $this->app->instance(MailReplyScanner::class, $scanner);

        $this->artisan('mail:scan-replies')
            ->assertExitCode(Command::SUCCESS);
    }
}
