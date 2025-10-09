<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Detectors;

use App\Services\Mail\Scanner\Contracts\MessageTypeDetectorInterface;
use Webklex\PHPIMAP\Message;

class BounceDetector implements MessageTypeDetectorInterface
{
    public function matches(Message $message): bool
    {
        $subject = $message->getSubject() ?? '';
        return preg_match('/(Mail Delivery Failed|Undeliverable)/i', $subject) === 1;
    }
}