<?php

declare(strict_types=1);

namespace App\Enum;

enum ProcessingStatusEnum: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Deleted = 'deleted';
}
