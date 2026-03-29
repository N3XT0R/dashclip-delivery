<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Mail\Scanner\MailReplyScanner;
use Illuminate\Console\Command;

class ScanMailReplies extends Command
{
    protected $signature = 'mail:scan-replies';
    protected $description = 'Scans the IMAP inbox for replies or bounces';

    public function handle(MailReplyScanner $scanner): int
    {
        if (defined('IS_TESTING') || app()->environment('production')) {
            $this->info('Scanning for mail replies and bounces...');
            $scanner->scan();
        }

        return self::SUCCESS;
    }
}
