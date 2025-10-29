<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\ChannelRepository;

class ChannelService
{
    public function __construct(protected ChannelRepository $channelRepository)
    {
    }
}