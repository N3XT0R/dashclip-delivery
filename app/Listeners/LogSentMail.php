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

        file_put_contents('test.log', print_r($headers->getNames(), true));

        MailLog::create([
            'message_id' => $headers->getHeaderBody('Message-ID'),
            'to' => $to,
            'subject' => $message->getSubject(),
            'meta' => ['headers' => $message->getHeaders()->toString()],
        ]);
    }
}