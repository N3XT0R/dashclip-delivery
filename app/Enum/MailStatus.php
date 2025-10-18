<?php

declare(strict_types=1);

namespace App\Enum;


enum MailStatus: string
{
    case Sent = 'sent';
    case Replied = 'replied';
    case Bounced = 'bounced';
    case Received = 'received';
}