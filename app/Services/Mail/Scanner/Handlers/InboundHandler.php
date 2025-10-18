<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Handlers;

use App\Enum\MailDirection;
use App\Enum\MailStatus;
use App\Repository\MailRepository;
use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\Message;

class InboundHandler implements MessageStrategyInterface
{
    public function __construct(private MailRepository $mailRepository)
    {
    }

    public function matches(Message $message): bool
    {
        return $message->getFolderPath() === 'INBOX';
    }

    public function handle(Message $message): void
    {
        $from = $message->getFrom()[0]->mail ?? '';
        $subject = $message->getSubject()->toString() ?? '';
        $messageId = $message->getMessageId()->toString();
        $createdAt = $message->getDate()?->toDate() ?? now();

        if ($this->mailRepository->existsByMessageId($messageId)) {
            Log::info("Mail already processed: {$messageId}");
            return;
        }

        $this->mailRepository->create([
            'message_id' => $messageId,
            'from' => $from,
            'subject' => $subject,
            'direction' => MailDirection::INBOUND,
            'status' => MailStatus::Received,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        Log::info("Inbound mail stored: {$subject} from {$from}");
    }
}