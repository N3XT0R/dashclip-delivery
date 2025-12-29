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

    public function handle(User $user, Channel $channel, ?User $causer = null): void
    {
        $this->channelOperatorService->revokeUserChannelAccess($user, $channel);
        activity('channel_access_revoked')
            ->event('revoked')
            ->performedOn($user)
            ->causedBy($causer ?? auth()->user())
            ->withProperties([
                'channel_id' => $channel->id,
                'user_id' => $user->id,
            ])
            ->log('Channel access revoked from user');
    }
}
