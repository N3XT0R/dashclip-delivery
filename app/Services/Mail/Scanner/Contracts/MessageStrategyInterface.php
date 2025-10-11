<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Contracts;

use Webklex\PHPIMAP\Message;

interface MessageStrategyInterface
{
    public function matches(Message $message): bool;

    public function handle(Message $message): void;
}