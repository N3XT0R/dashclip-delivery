<?php

declare(strict_types=1);

namespace App\Application\Channel\Application;

use App\Models\ChannelApplication;
use App\Services\Channel\ChannelOperatorService;
use App\Services\NotificationService;

final readonly class ApproveChannelAccess
{
    public function __construct(
        private NotificationService $notificationService,
        private ChannelOperatorService $channelOperatorService
    ) {
    }

    public function handle(ChannelApplication $channelApplication): void
    {
        $this->channelOperatorService->approveUserChannelAccess(
            $channelApplication->user,
            $channelApplication->channel
        );
        $this->notificationService->notifyChannelAccessApproved($channelApplication);
    }
}
