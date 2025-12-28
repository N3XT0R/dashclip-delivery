<?php

declare(strict_types=1);

namespace App\DTO\Channel;

class ChannelApplicationRequestDto
{
    public function __construct(
        public ?int $channelId,
        public string $note,
        public bool $otherChannelRequest = false,
        public ?string $newChannelName = null,
        public ?string $newChannelCreatorName = null,
        public ?string $newChannelEmail = null,
        public ?string $newChannelYoutubeName = null,
    ) {
    }
}