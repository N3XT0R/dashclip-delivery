<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use App\Facades\Cfg;
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
        Cfg::set('faq_email', 'support@example.com', 'email');

        $scanner = Mockery::mock(MailReplyScanner::class);
        $scanner->shouldReceive('scan')->once();

        $this->app->instance(MailReplyScanner::class, $scanner);

        $this->artisan('mail:scan-replies')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testItSkipsScannerWhenFaqEmailDisabled(): void
    {
        Cfg::set('faq_email', 0, 'email', 'bool');

        $scanner = Mockery::mock(MailReplyScanner::class);
        $scanner->shouldReceive('scan')->never();

        $this->app->instance(MailReplyScanner::class, $scanner);

        $this->artisan('mail:scan-replies')
            ->assertExitCode(Command::SUCCESS);
    }
}
