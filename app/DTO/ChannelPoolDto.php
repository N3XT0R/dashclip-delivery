<?php

declare(strict_types=1);

namespace App\DTO;


use Illuminate\Support\Collection;

class ChannelPoolDto
{
    public function __construct(
        public Collection $channels,
        public Collection $rotationPool,
        /** @var array<int,int> channel_id => quota */
        public array $channelQuota,
        /** @var array<int,array<int|string,int>> channel_id => [uploader_id => quota] */
        public array $uploaderQuotaMatrix,
    ) {
    }
}