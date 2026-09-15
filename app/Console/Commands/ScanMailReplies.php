<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\Mail\MailException;
use App\Services\Mail\Scanner\MailReplyScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanMailReplies extends Command
{
    protected $signature = 'mail:scan-replies';
    protected $description = 'Scans the mailbox for replies or bounces';

    /**
     * Scan the mailbox for replies and bounces.
     *
     * An unreachable mailbox is treated as a temporary condition: it is logged and the command still
     * reports success, so a short outage does not turn every scheduled run into a failed command.
     *
     * @param MailReplyScanner $scanner
     * @return int
     */
    public function handle(MailReplyScanner $scanner): int
    {
        if (defined('IS_TESTING') || app()->environment('production')) {
            $this->info('Scanning for mail replies and bounces...');

            try {
                $scanner->scan();
            } catch (MailException $e) {
                Log::error('Mailbox scan skipped, the mailbox is unreachable', ['exception' => $e]);
                $this->warn('Mailbox is currently unreachable, skipping this run.');

                return self::SUCCESS;
            }
        }

        return self::SUCCESS;
    }
}
