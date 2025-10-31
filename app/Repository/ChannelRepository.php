<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;

class ChannelRepository
{
    public function getActiveChannels(): Collection
    {
        return Channel::query()
            ->where('channels.is_video_reception_paused', false)
            ->orderBy('id')->get();
    }

    public function approve(Channel $channel): bool
    {
        return $channel->update([
            'is_video_reception_paused' => false,
            'approved_at' => now(),
        ]);
    }
}