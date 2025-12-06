<?php

declare(strict_types=1);

namespace App\Enum\Channel;

enum ApplicationEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
