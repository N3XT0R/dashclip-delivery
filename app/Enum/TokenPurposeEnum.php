<?php

declare(strict_types=1);

namespace App\Enum;

enum TokenPurposeEnum: string
{
    case CHANNEL_ACCESS_APPROVAL = 'channel_access_approval';
    case CHANNEL_ACTIVATION_APPROVAL = 'channel_activation_approval';
}
