<?php

declare(strict_types=1);

namespace App\Services\Channel;

use App\Models\Channel;
use App\Models\Team;
use App\Repository\ChannelRepository;
use Illuminate\Support\Collection;

/**
 * Determines the channels a submitter may pick as preferred channel while uploading.
 *
 * The selection mirrors the pool the distribution would use for that upload: the channels
 * assigned to the uploader's team when it has any, otherwise every channel that accepts videos.
 */
readonly class UploadTargetChannelService
{
    public function __construct(private ChannelRepository $channelRepository)
    {
    }

    /**
     * List the channels the given team may target, sorted by name.
     *
     * @param Team|null $team
     * @return Collection<int, Channel>
     */
    public function getSelectableChannels(?Team $team): Collection
    {
        $channels = $team instanceof Team && $team->channelAssignments()->exists()
            ? $this->channelRepository->getTeamAssignedChannels($team)
            : $this->channelRepository->getActiveChannels();

        return $channels
            ->sortBy(fn (Channel $channel) => mb_strtolower((string)$channel->getAttribute('name')))
            ->values();
    }

    /**
     * Resolve a submitted channel id to a channel the given team may actually target.
     *
     * @param Team|null $team
     * @param int|string|null $channelId
     * @return Channel|null null when nothing was selected or the channel is out of reach
     */
    public function resolveSelection(?Team $team, int|string|null $channelId): ?Channel
    {
        $value = trim((string)$channelId);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return $this->getSelectableChannels($team)
            ->first(fn (Channel $channel) => (int)$channel->getKey() === (int)$value);
    }
}
