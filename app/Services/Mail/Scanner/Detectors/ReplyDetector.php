<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Detectors;

use App\Services\Mail\Scanner\Contracts\MessageTypeDetectorInterface;
use Webklex\PHPIMAP\Message;

class ReplyDetector implements MessageTypeDetectorInterface
{
    public function matches(Message $message): bool
    {
        return $message->getInReplyTo() !== null;
    }
}
