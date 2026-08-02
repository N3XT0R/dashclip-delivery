<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enum\TokenPurposeEnum;
use App\Models\Channel;
use Carbon\CarbonInterface;

final class ChannelVideoReceptionPausedMail extends AbstractLoggedMail
{
    public function __construct(
        public readonly Channel $channel,
        public readonly string $plainToken,
        public readonly CarbonInterface $expireAt,
    ) {
        $this->subjectLine = __('mails.channel_reception_paused.subject', [
            'channel' => $channel->name,
        ]);
    }

    protected function viewName(): string
    {
        return 'emails.channel.video_reception_paused';
    }

    protected function viewData(): array
    {
        return [
            'channel'       => $this->channel,
            'expireAt'      => $this->expireAt,
            'reactivateUrl' => route('tokens.update', [
                'purpose' => TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
                'token'   => $this->plainToken,
            ]),
        ];
    }
}
