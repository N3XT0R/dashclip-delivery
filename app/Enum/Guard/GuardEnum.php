<?php

declare(strict_types=1);

namespace App\Enum\Guard;

enum GuardEnum: string
{
    case DEFAULT = 'web';
    case STANDARD = 'standard';
    case API = 'api';
}
