<?php

declare(strict_types=1);

namespace App\Application\Channel;

use App\Models\Channel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\UserRepository;

readonly class GetCurrentChannel
{
    public function __construct(
        private UserRepository $userRepository,
        private ChannelRepository $channelRepository
    ) {
    }

    public function handle(?User $user = null): ?Channel
    {
        if (!$user) {
            $user = $this->userRepository->getCurrentUser();
            if (!$user) {
                return null;
            }
        }

        return $this->channelRepository->getChannelsForUser($user)->first();
    }
}
