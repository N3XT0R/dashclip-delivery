<?php

declare(strict_types=1);

namespace App\Application\Channel\Application;

use App\Models\Channel;
use App\Models\User;
use App\Services\Channel\ChannelOperatorService;

final readonly class RevokeChannelAccess
{
    public function __construct(
        private ChannelOperatorService $channelOperatorService,
    ) {
    }

    public function handle(User $user, Channel $channel): void
    {
        $this->channelOperatorService->revokeUserChannelAccess($user, $channel);
    }
}
