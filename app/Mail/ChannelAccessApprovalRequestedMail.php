<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enum\TokenPurposeEnum;
use App\Models\ChannelApplication;
use Carbon\CarbonInterface;

final class ChannelAccessApprovalRequestedMail extends AbstractLoggedMail
{
    protected string $subjectLine;

    public function __construct(
        public ChannelApplication $channelApplication,
        public string $plainToken,
        public CarbonInterface $expireAt,
    ) {
        $this->subjectLine = __('mails.channel_access_request.subject');
    }

    protected function viewName(): string
    {
        return 'emails.channel.access_approval_requested';
    }

    protected function viewData(): array
    {
        return [
            'application' => $this->channelApplication,
            'channel' => $this->channelApplication->channel,
            'user' => $this->channelApplication->user,
            'expireAt' => $this->expireAt,
            'approveUrl' => route('tokens.update', [
                'purpose' => TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value,
                'token' => $this->plainToken,
            ]),
        ];
    }
}
