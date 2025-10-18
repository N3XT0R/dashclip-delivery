<?php

declare(strict_types=1);

namespace App\Enum;

enum MailDirection: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';
}