<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Handlers;

use App\Enum\MailStatus;
use App\Mail\NoReplyFAQMail;
use App\Models\MailLog;
use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use App\Services\Mail\Scanner\Contracts\MoveToFolderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webklex\PHPIMAP\Message;

class ReplyHandler implements MessageStrategyInterface, MoveToFolderInterface
{
    public function matches(Message $message): bool
    {
        return $message->getInReplyTo() !== null;
    }

    public function handle(Message $message): void
    {
        $from = $message->getFrom()[0]->mail ?? '';
        $inReplyTo = $message->getInReplyTo()->toString();

        if (!$inReplyTo) {
            $message->setFlag('Seen');
            return;
        }

        $log = MailLog::where('message_id', 'like', '%'.$inReplyTo.'%')->first();
        if (!$log) {
            $message->setFlag('Seen');
            return;
        }

        if ($log->status !== MailStatus::Replied) {
            Mail::to($from)->queue(new NoReplyFAQMail());
            $log->update([
                'status' => MailStatus::Replied,
                'replied_at' => now(),
            ]);
            Log::info("Auto-reply sent to {$from}");
        }
    }

    public function getMoveToFolderPath(): string
    {
        return 'Processed/Replies';
    }
    
}
