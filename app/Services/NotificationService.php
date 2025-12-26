<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChannelApplication;
use App\Notifications\ChannelAccessApprovedNotification;

class NotificationService
{
    /**
     * Send channel access approved notification to the user.
     * @param ChannelApplication $channelApplication
     * @return void
     */
    public function sendChannelAccessApprovedNotification(ChannelApplication $channelApplication): void
    {
        $user = $channelApplication->user;
        $user->notify(new ChannelAccessApprovedNotification($channelApplication));
    }
}
