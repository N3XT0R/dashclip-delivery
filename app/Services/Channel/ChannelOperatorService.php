<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Repository\ChannelRepository;
use App\Repository\UserRepository;

class ChannelOperatorService
{

    public function __construct(
        private UserRepository $userRepository,
        private ChannelRepository $channelRepository
    ) {
    }
}