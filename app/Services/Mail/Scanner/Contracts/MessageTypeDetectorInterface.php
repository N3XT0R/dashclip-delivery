<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Contracts;

use Webklex\PHPIMAP\Message;

interface MessageTypeDetectorInterface
{
    public function matches(Message $message): bool;
}