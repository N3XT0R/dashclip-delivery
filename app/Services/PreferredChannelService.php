<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Repository\ChannelRepository;

class PreferredChannelService
{
    public function __construct(private readonly ChannelRepository $channelRepository)
    {
    }

    /**
     * Resolve a raw preferred_channel value from info.csv to a channel id.
     *
     * Rules:
     *  - '' / whitespace only     → null (no preference)
     *  - digits only              → lookup by channel id (no name fallback)
     *  - otherwise                → lookup by name (case-insensitive, trimmed)
     * The channel must exist AND must not be paused
     * (is_video_reception_paused === false), otherwise null is returned.
     *
     * @param string $raw
     * @return int|null resolved channel id, or null when not resolvable
     */
    public function resolveRawValue(string $raw): ?int
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $channel = ctype_digit($value)
            ? $this->channelRepository->findById((int) $value)
            : $this->channelRepository->findByNameInsensitive($value);

        if (!$channel instanceof Channel || $channel->is_video_reception_paused) {
            return null;
        }

        return (int) $channel->getKey();
    }
}
