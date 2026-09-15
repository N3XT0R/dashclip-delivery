<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use App\Exceptions\Mail\MailConnectionException;
use App\Services\Mail\Scanner\MailReplyScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    public function testItReportsSuccessWhenTheImapConnectionFails(): void
    {
        $scanner = Mockery::mock(MailReplyScanner::class);
        $scanner->shouldReceive('scan')
            ->once()
            ->andThrow(MailConnectionException::forAccount('default', new \RuntimeException('connection failed')));

        $this->app->instance(MailReplyScanner::class, $scanner);

        Log::shouldReceive('error')
            ->once()
            ->with('Mailbox scan skipped, the mailbox is unreachable', Mockery::type('array'));

        $this->artisan('mail:scan-replies')
            ->assertExitCode(Command::SUCCESS);
    }
}
