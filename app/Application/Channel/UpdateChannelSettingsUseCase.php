<?php

declare(strict_types=1);

namespace App\Application\Channel;

use App\Models\Channel;
use App\Repository\ChannelRepository;

/**
 * Applies the operator-editable channel settings, the same ones the channel settings page offers.
 */
readonly class UpdateChannelSettingsUseCase
{
    /**
     * Settings a channel operator may change.
     *
     * @var list<string>
     */
    public const array EDITABLE_FIELDS = [
        'name',
        'creator_name',
        'email',
        'youtube_name',
        'is_video_reception_paused',
        'show_on_homepage',
    ];

    public function __construct(private ChannelRepository $channelRepository)
    {
    }

    /**
     * @param Channel $channel
     * @param array<string, mixed> $settings subset of EDITABLE_FIELDS; other keys are ignored
     * @return Channel the updated channel
     */
    public function handle(Channel $channel, array $settings): Channel
    {
        return $this->channelRepository->update(
            $channel,
            array_intersect_key($settings, array_flip(self::EDITABLE_FIELDS)),
        );
    }
}
