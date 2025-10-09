<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Enum\MailStatus;
use App\Mail\NoReplyFAQMail;
use App\Models\MailLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Webklex\IMAP\Facades\Client;

class MailReplyScanner
{
    public function scan(?string $account = null): void
    {
        $client = Client::account($account);
        $client->connect();

        $inbox = $client->getFolder('INBOX');
        $messages = $inbox->messages()->unseen()->get();

        foreach ($messages as $message) {
            try {
                $this->processMessage($message);
            } catch (Throwable $e) {
                Log::error('IMAP processing failed', [
                    'error' => $e->getMessage(),
                    'from' => optional($message->getFrom()[0])->mail,
                    'subject' => $message->getSubject(),
                ]);
                $message->setFlag('Flagged');
            }
        }
    }

    private function processMessage($message): void
    {
        $subject = $message->getSubject() ?? '';
        $body = $message->getTextBody() ?? '';
        $from = $message->getFrom()[0]->mail ?? '';

        // 🧩 Bounce-Erkennung
        if ($this->isBounce($subject)) {
            $this->handleBounce($message, $body);
            return;
        }

        // 🧩 Antwort-Erkennung
        $inReplyTo = $message->getInReplyTo();
        if ($inReplyTo && ($log = MailLog::where('message_id', $inReplyTo)->first())) {
            $this->handleReply($message, $log, $from);
            return;
        }

        $message->setFlag('Seen');
    }

    private function isBounce(string $subject): bool
    {
        return preg_match('/(Mail Delivery Failed|Undeliverable)/i', $subject) === 1;
    }

    private function handleBounce($message, string $body): void
    {
        preg_match('/Message-ID:\s*<([^>]+)>/i', $body, $matches);
        $original = $matches[1] ?? null;

        if ($original && ($log = MailLog::where('message_id', $original)->first())) {
            $log->update([
                'status' => MailStatus::Bounced,
                'bounced_at' => now(),
                'meta' => array_merge($log->meta ?? [], [
                    'bounce_excerpt' => substr($body, 0, 400)
                ]),
            ]);
            Log::info("Bounce for {$original}");
        }

        $message->moveToFolder('Processed/Bounces');
    }

    private function handleReply($message, MailLog $log, string $from): void
    {
        if ($log->status !== MailStatus::Replied) {
            Mail::to($from)->queue(new NoReplyFAQMail());
            $log->update([
                'status' => MailStatus::Replied,
                'replied_at' => now(),
            ]);
            Log::info("Auto-reply sent to {$from}");
        }

        $message->moveToFolder('Processed/Replies');
    }
}