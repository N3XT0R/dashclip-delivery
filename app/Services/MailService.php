<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChannelApplication;

class MailService
{
    public function sendChannelAccessApprovalRequestedMail(string $owner, ChannelApplication $channelApplication): void
    {
        $tokenService = app(ActionTokenService::class);
        $actionToken = $tokenService->issue(
            purpose: self::class,
            subject: $channelApplication,
            meta: [
                'channel_application_id' => $channelApplication->getKey(),
                'user_id' => $channelApplication->user->getKey(),
                'owner' => $owner,
            ],
        );
    }
}
