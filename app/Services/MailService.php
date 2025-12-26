<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\TokenPurposeEnum;
use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Mail\ChannelWelcomeMail;
use App\Mail\NewOfferMail;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\ChannelApplication;
use Carbon\Carbon;
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
        $expireAt = Carbon::now()->addMonth();
        $tokenService = app(ActionTokenService::class);
        $actionToken = $tokenService->issue(
            purpose: TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL,
            subject: $channelApplication,
            expiresAt: $expireAt,
            meta: [
                'user_id' => $channelApplication->user->getKey(),
                'channel_id' => $channelApplication->channel->getKey(),
                'owner' => $owner,
            ],
        );

        Mail::to($owner)->send(
            new ChannelAccessApprovalRequestedMail(
                $channelApplication,
                $actionToken,
                $expireAt
            )
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

    public function sendNewOfferMail(
        Channel $channel,
        Batch $assignBatch,
        Carbon $expireDate,
        bool $isChannelOperator
    ): void {
        $linkService = app(LinkService::class);
        $offerUrl = $linkService->getOfferUrl($assignBatch, $channel, $expireDate);
        $unusedUrl = $linkService->getUnusedUrl($assignBatch, $channel, $expireDate);

        Mail::to($channel->getAttribute('email'))->queue(
            new NewOfferMail($assignBatch, $channel, $offerUrl, $expireDate, $unusedUrl, $isChannelOperator)
        );
    }
}
