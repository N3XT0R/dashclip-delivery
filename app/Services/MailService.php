<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\TokenPurposeEnum;
use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Notifications\ChannelAccessApprovedNotification;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send channel access approval requested mail to the channel owner.
     * @param string $owner
     * @param ChannelApplication $channelApplication
     * @throws \Random\RandomException
     */
    public function sendChannelAccessApprovalRequestedMail(
        string $owner,
        ChannelApplication $channelApplication
    ): void {
        $tokenService = app(ActionTokenService::class);
        $actionToken = $tokenService->issue(
            purpose: TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            subject: $channelApplication,
            meta: [
                'user_id' => $channelApplication->user->getKey(),
                'channel_id' => $channelApplication->channel->getKey(),
                'owner' => $owner,
            ],
        );

        Mail::to($owner)->send(
            new ChannelAccessApprovalRequestedMail($channelApplication, $actionToken)
        );
    }

    /**
     * Send channel welcome mail to the channel email.
     * @param Channel $channel
     */
    public function sendChannelWelcomeMail(Channel $channel): void
    {
        Mail::to($channel->email)->send(new ChannelWelcomeMail($channel));
    }

    /**
     * Send channel access approved mail to the applicant.
     * @param ChannelApplication $channelApplication
     * @return void
     */
    public function sendChannelAccessApprovedMail(ChannelApplication $channelApplication): void
    {
        $user = $channelApplication->user;
        $user->notify(new ChannelAccessApprovedNotification($channelApplication));
    }
}
