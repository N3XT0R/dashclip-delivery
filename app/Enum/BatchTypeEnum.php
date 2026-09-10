<?php

declare(strict_types=1);

namespace App\Enum;

enum BatchTypeEnum: string
{
    case ASSIGN = 'assign';

    case INGEST = 'ingest';

    case NOTIFY = 'notify';
    case REMOVE = 'remove';

    case API = 'api';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
