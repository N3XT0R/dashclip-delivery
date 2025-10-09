<?php

declare(strict_types=1);

namespace App\Services\Mail\Scanner\Contracts;


use Webklex\PHPIMAP\Message;

interface MessageHandlerInterface
{
    public function handle(Message $message): void;
}