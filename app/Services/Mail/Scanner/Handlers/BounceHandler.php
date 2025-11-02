<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Handlers;

use App\Enum\MailStatus;
use App\Models\MailLog;
use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use App\Services\Mail\Scanner\Contracts\MoveToFolderInterface;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\Message;

class BounceHandler implements MessageStrategyInterface, MoveToFolderInterface
{

    public function matches(Message $message): bool
    {
        $subject = $message->getSubject()->toString() ?? '';
        return preg_match('/(Mail Delivery Failed|Undeliverable|Undelivered)/i', $subject) === 1;
    }

    public function handle(Message $message): void
    {
        $body = $message->getTextBody() ?? '';
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
    }

    public function getMoveToFolderPath(): string
    {
        return 'Processed/Bounces';
    }


}