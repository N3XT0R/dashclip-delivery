<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\TokenPurposeEnum;
use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Mail\ChannelWelcomeMail;
use App\Mail\NewOfferMail;
use App\Mail\NoReplyFAQMail;
use App\Mail\ReminderMail;
use App\Mail\UserWelcomeMail;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use App\Support\Mail\MailAddressResolver;
use Carbon\Carbon;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

readonly class MailService
{

    /**
     * Queue the given mail to the given mailable.
     * @param string|User $mailable
     * @param MailableContract $mail
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Contracts\Container\CircularDependencyException
     */
    private function queueMail(string|User $mailable, MailableContract $mail): mixed
    {
        $email = app(MailAddressResolver::class)->resolve($mailable);
        return Mail::to($email)->queue($mail);
    }


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

        $this->queueMail(
            $owner,
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
        $this->queueMail($channel->email, new ChannelWelcomeMail($channel));
    }

    /**
     * Send new offer mail to the channel email.
     * @param Channel $channel
     * @param Batch $assignBatch
     * @param Carbon $expireDate
     * @param bool $isChannelOperator
     */
    public function sendNewOfferMail(
        Channel $channel,
        Batch $assignBatch,
        Carbon $expireDate,
        bool $isChannelOperator
    ): void {
        $linkService = app(LinkService::class);
        if ($isChannelOperator) {
            $offerUrl = route('filament.standard.auth.login');
        } else {
            $offerUrl = $linkService->getOfferUrl($assignBatch, $channel, $expireDate);
        }

        $unusedUrl = $linkService->getUnusedUrl($assignBatch, $channel, $expireDate);

        $this->queueMail(
            $channel->email,
            new NewOfferMail($assignBatch, $channel, $offerUrl, $expireDate, $unusedUrl, $isChannelOperator)
        );
    }

    /**
     * Send user welcome mail to the user email.
     * @param User $user
     * @param bool $fromBackend
     * @param string|null $plainPassword
     * @return void
     */
    public function sendUserWelcomeEmail(User $user, bool $fromBackend = false, ?string $plainPassword = null): void
    {
        $this->queueMail($user->email, new UserWelcomeMail($user, $fromBackend, $plainPassword));
    }

    /**
     * Send reminder mail to the channel email.
     * @param Channel $channel
     * @param Collection<int, Assignment> $assignments
     */
    public function sendReminderMail(Channel $channel, Collection $assignments): void
    {
        $linkService = app(LinkService::class);
        /**
         * @var Assignment $first
         */
        $first = $assignments->first();
        $batch = $first->batch;
        $expireDate = $first->expires_at;
        $offerUrl = $linkService->getOfferUrl($batch, $channel, $expireDate);

        $this->queueMail(
            $channel->email,
            new ReminderMail($channel, $offerUrl, $expireDate, $assignments)
        );
    }

    /**
     * Send FAQ no-reply mail to the given email address.
     * @param string $email
     * @return void
     */
    public function sendFaqMail(string $email): void
    {
        $this->queueMail($email, new NoReplyFAQMail());
    }
}
