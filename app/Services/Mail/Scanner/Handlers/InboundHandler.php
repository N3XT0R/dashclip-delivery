<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Handlers;

use App\Enum\MailDirection;
use App\Enum\MailStatus;
use App\Repository\MailRepository;
use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
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


    private function getDateByMessage(Message $message): Carbon|\Carbon\Carbon|CarbonInterface
    {
        return $message->getHeader()?->get('Date')?->toDate()
            ?? $message->getDate()?->toDate()
            ?? now();
    }

    public function handle(Message $message): void
    {
        $from = $message->getFrom()[0]->mail ?? '';
        $to = $message->getTo()->toString();
        $subject = $message->getSubject()->toString() ?? '';
        $messageId = $message->getMessageId()->toString();
        $createdAt = $this->getDateByMessage($message);

        if ($this->mailRepository->existsByMessageId($messageId)) {
            Log::info("Mail already processed: {$messageId}");
            return;
        }


        $body = $this->decodeBody($message);

        $this->mailRepository->create([
            'message_id' => $messageId,
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'direction' => MailDirection::INBOUND,
            'status' => MailStatus::Received,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'meta' => [
                'headers' => $this->getHeadersByMessage($message),
                'content' => $body['content'],
                'content_format' => $body['format'],
            ],
        ]);

        Log::info("Inbound mail stored", ['subject' => $subject, 'from' => $from]);
    }

    /**
     * Resolve the readable body of a message.
     *
     * The raw body still carries the MIME structure and the transfer encoding,
     * so storing it leaves the viewer with boundaries or base64 instead of text.
     * Prefer the decoded HTML part, fall back to the decoded text part, and keep
     * the raw payload only when the message offers neither.
     *
     * @param Message $message
     * @return array{content: string, format: string}
     */
    protected function decodeBody(Message $message): array
    {
        foreach ([['getHTMLBody', 'html'], ['getTextBody', 'text']] as [$method, $format]) {
            try {
                $body = (string)$message->{$method}();
            } catch (\Throwable) {
                continue;
            }

            if (trim($body) !== '') {
                return ['content' => $body, 'format' => $format];
            }
        }

        return ['content' => (string)$message->getRawBody(), 'format' => 'raw'];
    }

    /**
     * Collect the raw header lines of a message.
     *
     * A message without a readable header must not abort the scan, so every
     * failure falls back to the parsed attributes and finally to an empty list.
     *
     * @param Message $message
     * @return array<int|string, mixed>
     */
    protected function getHeadersByMessage(Message $message): array
    {
        try {
            $raw = $message->getHeader()?->raw;

            if (is_string($raw) && trim($raw) !== '') {
                return array_map(static fn(string $line): string => trim($line), explode("\r\n", $raw));
            }
        } catch (\Throwable $e) {
            Log::warning('Could not read inbound mail headers', ['error' => $e->getMessage()]);
        }

        try {
            return $message->getHeader()?->getAttributes() ?? [];
        } catch (\Throwable $e) {
            Log::warning('Could not read inbound mail header attributes', ['error' => $e->getMessage()]);

            return [];
        }
    }
}