<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Stubs;

use App\DTO\ChannelPoolDto;

final class FakeChannelPoolDtoFactory
{
    public static function make(): ChannelPoolDto
    {
        return new ChannelPoolDto(collect(), collect(), []);
    }
}