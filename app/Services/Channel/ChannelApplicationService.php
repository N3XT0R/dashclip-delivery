<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Repository\ChannelRepository;

class ChannelApplicationService
{
    public function __construct(private ChannelRepository $channelRepository)
    {
    }
}
