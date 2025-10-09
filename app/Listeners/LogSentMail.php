<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\MailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogSentMail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $headers = $message->getHeaders();
        $to = $message->getTo()[0]->getAddress();

        if (!$to) {
            Log::warning('MessageSent without recipient', ['message' => $message]);
            return;
        }

        $messageId = $message->getHeaders()->get('Message-ID')?->getBodyAsString()
            ?? $event->data['headers']['Message-ID']
            ?? null;

        $appId = $message->getHeaders()->get('X-App-Message-ID')?->getBodyAsString()
            ?? $event->data['headers']['X-App-Message-ID']
            ?? null;

        MailLog::create([
            'message_id' => $messageId,
            'internal_id' => $appId,
            'to' => $to,
            'subject' => $message->getSubject(),
            'meta' => ['headers' => $message->getHeaders()->toString()],
        ]);
    }
}