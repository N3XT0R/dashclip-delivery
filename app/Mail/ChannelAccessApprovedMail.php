<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ChannelApplication;

final class ChannelAccessApprovedMail extends AbstractLoggedMail
{
    protected string $subjectLine;

    public function __construct(
        public ChannelApplication $channelApplication
    ) {
        $this->subjectLine = __('tokens.channel_access_approved.subject');
    }

    protected function viewName(): string
    {
        return 'emails.channel.access_approved';
    }

    protected function viewData(): array
    {
        return [
            'application' => $this->channelApplication,
            'channel' => $this->channelApplication->channel,
        ];
    }
}
