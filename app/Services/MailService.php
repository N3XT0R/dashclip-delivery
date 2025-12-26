<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Models\ChannelApplication;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send channel access approval requested mail to the channel owner.
     * @param string $owner
     * @param ChannelApplication $channelApplication
     * @return SentMessage
     * @throws \Random\RandomException
     */
    public function sendChannelAccessApprovalRequestedMail(
        string $owner,
        ChannelApplication $channelApplication
    ): SentMessage {
        $tokenService = app(ActionTokenService::class);
        $actionToken = $tokenService->issue(
            purpose: self::class,
            subject: $channelApplication,
            meta: [
                'user_id' => $channelApplication->user->getKey(),
                'channel_id' => $channelApplication->channel->getKey(),
                'owner' => $owner,
            ],
        );

        return Mail::to($owner)->send(
            new ChannelAccessApprovalRequestedMail($channelApplication, $actionToken)
        );
    }
}
