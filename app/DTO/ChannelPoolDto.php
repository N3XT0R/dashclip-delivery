<?php

declare(strict_types=1);

namespace App\DTO;


use Illuminate\Support\Collection;

readonly class ChannelPoolDto
{
    public function __construct(
        public Collection $channels,
        public Collection $rotationPool,
        /** @var array<int,int> channel_id => quota */
        public array $quota,
    ) {
    }
}