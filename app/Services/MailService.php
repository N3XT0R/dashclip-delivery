<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Models\ChannelApplication;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendChannelAccessApprovalRequestedMail(
        string $owner,
        ChannelApplication $channelApplication
    ): SentMessage {
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

        return Mail::to($owner)->send(
            new ChannelAccessApprovalRequestedMail($channelApplication, $actionToken)
        );
    }
}
