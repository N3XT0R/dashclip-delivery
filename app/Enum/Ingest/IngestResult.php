<?php

declare(strict_types=1);

namespace App\Enum\Ingest;

enum IngestResult: string
{
    case NEW = 'new';
    case DUPS = 'dups';
    case ERR = 'err';
}
