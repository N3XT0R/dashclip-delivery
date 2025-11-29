<?php

declare(strict_types=1);

namespace Tests\Integration\Services\stubs;

use App\DTO\ChannelPoolDto;
use Illuminate\Support\Collection;

final class FakeChannelPoolDtoFactory
{
    public static function make(): ChannelPoolDto
    {
        return new ChannelPoolDto(collect(), collect(), []);
    }
}
