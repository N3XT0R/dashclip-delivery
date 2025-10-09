<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Handlers;

use App\Enum\MailStatus;
use App\Models\MailLog;
use App\Services\Mail\Scanner\Contracts\MessageHandlerInterface;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\Message;

class BounceHandler implements MessageHandlerInterface
{
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

        $message->moveToFolder('Processed/Bounces');
    }
}