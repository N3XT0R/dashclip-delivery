<?php

declare(strict_types=1);

namespace App\Enum\Channel;

enum ApplicationEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function nonRejected(): array
    {
        return [
            self::PENDING->value,
            self::APPROVED->value,
        ];
    }

    public static function all(): array
    {
        return [
            self::PENDING->value,
            self::APPROVED->value,
            self::REJECTED->value,
        ];
    }
}
