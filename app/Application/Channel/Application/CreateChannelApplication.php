<?php

declare(strict_types=1);

namespace App\Application\Channel\Application;

use App\DTO\Channel\ChannelApplicationRequestDto;
use App\Models\User;
use App\Services\ChannelService;

class CreateChannelApplication
{

    public function __construct(private ChannelService $channelService)
    {
    }

    /**
     * Handle the creation of a channel application.
     * @param array $data
     * @param User $user
     * @return void
     */
    public function handle(array $data, User $user): void
    {
        $dto = new ChannelApplicationRequestDto(
            channelId: $data['channel_id'] ?? null,
            note: $data['note'] ?? '',
            otherChannelRequest: $data['other_channel_request'] ?? false,
            newChannelName: $data['new_channel_name'] ?? null,
            newChannelCreatorName: $data['new_channel_creator_name'] ?? null,
            newChannelEmail: $data['new_channel_email'] ?? null,
            newChannelYoutubeName: $data['new_channel_youtube_name'] ?? null,
        );
        $this->channelService->applyForAccess($dto, $user);
    }
}
