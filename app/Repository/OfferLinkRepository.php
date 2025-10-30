<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Batch;
use App\Models\Channel;
use App\Models\OfferLinkClick;
use App\Models\User;

class OfferLinkRepository
{

    public function createOfferLinkClick(
        Batch $batch,
        Channel $channel,
        string $userAgent,
        ?User $user = null
    ): OfferLinkClick {
        return OfferLinkClick::create([
            'batch_id' => $batch->getKey(),
            'channel_id' => $channel->getKey(),
            'clicked_at' => now(),
            'user_id' => $user?->getKey(),
            'user_agent' => substr($userAgent, 0, 500),
        ]);
    }
}