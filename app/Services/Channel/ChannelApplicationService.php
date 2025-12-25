<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Models\ChannelApplication as ChannelApplicationModel;
use App\Models\User;
use App\Services\ChannelService;

class ChannelApplicationService
{
    public function __construct(private ChannelService $channelService)
    {
    }

    public function approveChannelApplication(ChannelApplicationModel $channelApplication, ?User $user = null): bool
    {
        $result = false;
        $channelService = $this->channelService;
        $isNewChannel = $channelApplication->isNewChannel();
        if ($isNewChannel) {
            $channel = $channelService->createNewChannelByChannelApplication($channelApplication);
        } else {
            $channel = $channelApplication->channel;
        }


        return $result;
    }
}
