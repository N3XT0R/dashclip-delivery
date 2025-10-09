<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enum\MailStatus;
use App\Mail\NoReplyFAQMail;
use App\Models\MailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Webklex\IMAP\Facades\Client;

class ScanMailReplies extends Command
{
    protected $signature = 'mail:scan-replies';
    protected $description = 'Scans the IMAP inbox for replies or bounces';

    public function handle(): int
    {
        $client = Client::account();
        $client->connect();

        $inbox = $client->getFolder('INBOX');
        $messages = $inbox->messages()->unseen()->get();

        foreach ($messages as $message) {
            try {
                $subject = $message->getSubject() ?? '';
                $body = $message->getTextBody() ?? '';
                $from = $message->getFrom()[0]->mail ?? '';

                // 🧩 Bounce-Erkennung
                if (preg_match('/(Mail Delivery Failed|Undeliverable)/i', $subject)) {
                    preg_match('/Message-ID:\s*<([^>]+)>/i', $body, $matches);
                    $original = $matches[1] ?? null;

                    if ($original && ($log = MailLog::where('message_id', $original)->first())) {
                        $log->update([
                            'status' => MailStatus::Bounced,
                            'bounced_at' => now(),
                            'meta' => array_merge($log->meta ?? [], ['bounce_excerpt' => substr($body, 0, 400)]),
                        ]);
                        Log::info("Bounce for {$original}");
                    }

                    $message->moveToFolder('Processed/Bounces');
                    continue;
                }

                // 🧩 Antwort-Erkennung
                $inReplyTo = $message->getInReplyTo();
                if ($inReplyTo && ($log = MailLog::where('message_id', $inReplyTo)->first())) {
                    if ($log->status !== MailStatus::Replied) {
                        Mail::to($from)->queue(new NoReplyFAQMail());
                        $log->update([
                            'status' => MailStatus::Replied,
                            'replied_at' => now(),
                        ]);
                        Log::info("Auto-reply sent to {$from}");
                    }

                    $message->moveToFolder('Processed/Replies');
                    continue;
                }

                // Sonstiges
                $message->setFlag('Seen');
            } catch (Throwable $e) {
                Log::error('IMAP processing failed', ['error' => $e->getMessage()]);
                $message->setFlag('Flagged');
            }
        }

        return self::SUCCESS;
    }
}
