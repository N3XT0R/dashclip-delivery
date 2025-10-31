<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Channel;
use App\Repository\ChannelRepository;
use InvalidArgumentException;

class ChannelService
{
    public function __construct(protected ChannelRepository $channelRepository)
    {
    }

    /**
     * Prepare active channels, rotation pool, and quota mapping.
     * @param  int|null  $quotaOverride
     * @return array
     */
    public function prepareChannelsAndPool(?int $quotaOverride): array
    {
        $channels = $this->channelRepository->getActiveChannels();

        $rotationPool = collect();
        foreach ($channels as $channel) {
            $rotationPool = $rotationPool->merge(
                array_fill(0, max(1, (int)$channel->weight), $channel)
            );
        }

        /** @var array<int,int> $quota */
        $quota = $channels
            ->mapWithKeys(fn(Channel $c) => [$c->getKey() => (int)($quotaOverride ?: $c->weekly_quota)])
            ->all();

        return [$channels, $rotationPool, $quota];
    }

    public function approve(Channel $channel, string $approvalToken): void
    {
        $expected = $channel->getApprovalToken();
        if ($approvalToken !== $expected) {
            throw new InvalidArgumentException('Ungültiger Bestätigungslink.');
        }

        $this->channelRepository->approve($channel);
    }
}