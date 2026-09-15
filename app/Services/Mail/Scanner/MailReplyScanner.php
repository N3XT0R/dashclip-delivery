<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner;

use App\Exceptions\Mail\MailConnectionException;
use App\Exceptions\Mail\MailFolderNotFoundException;
use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use App\Services\Mail\Scanner\Contracts\MoveToFolderInterface;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Client as ClientAlias;
use Webklex\PHPIMAP\Message;

class MailReplyScanner
{
    /** @param  MessageStrategyInterface  $handlers */
    public function __construct(private readonly iterable $handlers)
    {
    }

    private function createFolder(ClientAlias $client, string $path): void
    {
        if (!$client->getFolder($path)) {
            $client->createFolder($path);
            Log::info('Folder '.$path.' created');
        }
    }

    /**
     * Scan the configured mailbox for unread replies and bounces.
     *
     * @param string|null $account Configured mailbox account identifier, null for the default account.
     *
     * @throws MailConnectionException When the mailbox cannot be reached or its folders cannot be read.
     * @throws MailFolderNotFoundException When the server does not expose an INBOX folder.
     */
    public function scan(?string $account = null): void
    {
        try {
            $client = Client::account($account);
            $client->connect();
            $inbox = $client->getFolder('INBOX');
        } catch (Throwable $e) {
            throw MailConnectionException::forAccount($account, $e);
        }

        if ($inbox === null) {
            throw MailFolderNotFoundException::forPath('INBOX', $account);
        }

        $messages = $inbox->messages()->unseen()->get();

        foreach ($messages as $message) {
            try {
                $this->dispatch($client, $message);
            } catch (Throwable $e) {
                Log::error('IMAP processing failed', ['exception' => $e]);
                $message->setFlag('Flagged');
            }
        }
    }

    private function dispatch(ClientAlias $client, Message $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof MessageStrategyInterface && $handler->matches($message)) {
                $handler->handle($message);

                if ($handler instanceof MoveToFolderInterface && app()->hasDebugModeEnabled() === false) {
                    $path = $handler->getMoveToFolderPath();
                    $this->createFolder($client, $path);
                    $message->move($path);
                }
            }
        }
        $message->setFlag('Seen');
    }
}
