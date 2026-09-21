<?php

declare(strict_types=1);

namespace App\Application\Channel;

use App\Models\Channel;
use App\Repository\ChannelRepository;
use Illuminate\Support\Facades\Storage;

/**
 * Removes a channel's logo file and clears the reference to it.
 */
readonly class RemoveChannelLogoUseCase
{
    public function __construct(private ChannelRepository $channelRepository)
    {
    }

    /**
     * @param Channel $channel
     * @return Channel the updated channel
     */
    public function handle(Channel $channel): Channel
    {
        $previous = $channel->logo_path;
        $updated = $this->channelRepository->update($channel, ['logo_path' => null]);

        if (is_string($previous) && $previous !== '') {
            Storage::disk(ReplaceChannelLogoUseCase::DISK)->delete($previous);
        }

        return $updated;
    }
}
