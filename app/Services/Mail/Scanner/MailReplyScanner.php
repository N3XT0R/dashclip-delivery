<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner;

use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Message;

class MailReplyScanner
{
    /** @param  MessageStrategyInterface  $handlers */
    public function __construct(private readonly iterable $handlers)
    {
    }

    private function shouldIgnore(Message $message): bool
    {
        return $message->getHeader()?->has('Auto-Submitted') ?? false;
    }

    public function scan(?string $account = null): void
    {
        $client = Client::account($account);
        $client->connect();
        $messages = $client->getFolder('INBOX')?->messages()->unseen()->get();


        foreach ($messages as $message) {
            try {
                $this->dispatch($message);
            } catch (Throwable $e) {
                Log::error('IMAP processing failed', ['exception' => $e]);
                $message->setFlag('Flagged');
            }
        }
    }

    private function dispatch(Message $message): void
    {
        if ($this->shouldIgnore($message)) {
            //$message->setFlag('Seen');
            return;
        }

        foreach ($this->handlers as $handler) {
            if ($handler instanceof MessageStrategyInterface && $handler->matches($message)) {
                $handler->handle($message);
            }
        }
        //$message->setFlag('Seen');
    }
}