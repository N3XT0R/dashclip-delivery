<?php

declare(strict_types=1);

namespace App\Enum\Channel;

enum ChannelConfigType: string
{
    case Bool = 'bool';
    case Int = 'int';
    case Json = 'json';
    case Datetime = 'datetime';
    case Encrypted = 'encrypted';
    case String = 'string';
}

