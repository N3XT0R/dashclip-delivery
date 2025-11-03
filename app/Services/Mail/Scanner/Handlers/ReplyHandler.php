<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Handlers;

use App\Enum\MailStatus;
use App\Mail\NoReplyFAQMail;
use App\Repository\MailRepository;
use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use App\Services\Mail\Scanner\Contracts\MoveToFolderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Message;

class ReplyHandler implements MessageStrategyInterface, MoveToFolderInterface
{
    public function __construct(private MailRepository $mailRepository)
    {
    }

    public function matches(Message $message): bool
    {
        return $message->getInReplyTo() !== null;
    }

    private function shouldIgnore(Message $message): bool
    {
        return $message->getHeader()?->has('Auto-Submitted') ?? false;
    }

    /**
     * @throws RuntimeException
     * @throws MessageFlagException
     * @throws EventNotFoundException
     * @throws ResponseException
     * @throws ImapBadRequestException
     * @throws ConnectionFailedException
     * @throws AuthFailedException
     * @throws ImapServerErrorException
     */
    public function handle(Message $message): void
    {
        if ($this->shouldIgnore($message)) {
            $message->setFlag('Seen');
            Log::info('Message '.$message->getMessageId()->toString().' was ignored');
            return;
        }
        $from = $message->getFrom()[0]->mail ?? '';
        $inReplyTo = $message->getInReplyTo()->toString();

        if (!$inReplyTo) {
            return;
        }

        $log = $this->mailRepository->findMailByInReplyTo($inReplyTo);
        if (!$log) {
            return;
        }

        if ($log->status !== MailStatus::Replied) {
            $mail = new NoReplyFAQMail();
            Mail::to($from)->queue($mail);
            $this->mailRepository->updateStatus($log, MailStatus::Replied);
            Log::info("Auto-reply sent to {$from}", ['to' => $from]);
        }
    }

    public function getMoveToFolderPath(): string
    {
        return 'Processed/Replies';
    }

}
