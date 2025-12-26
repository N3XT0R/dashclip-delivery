<?php

declare(strict_types=1);

namespace App\Application\Channel\Application;

use App\Models\ChannelApplication;
use App\Services\ChannelService;
use App\Services\NotificationService;

class ApproveChannelAccess
{
    public function __construct(
        private NotificationService $notificationService,
        private ChannelService $channelService
    ) {
    }

    public function handle(ChannelApplication $channelApplication): void
    {
    }
}
