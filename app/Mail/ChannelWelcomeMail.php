<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enum\TokenPurposeEnum;
use App\Models\Channel;
use Illuminate\Mail\Mailables\Envelope;

class ChannelWelcomeMail extends AbstractLoggedMail
{

    public function __construct(
        public Channel $channel,
        public string $plainToken,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mails.channel_welcome_email.subject'),
        );
    }


    protected function viewName(): string
    {
        return 'emails.channel-welcome';
    }


    public function viewData(): array
    {
        return [
            'channel' => $this->channel,
            'approveUrl' => route('tokens.update', [
                'purpose' => TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value,
                'token' => $this->plainToken,
            ]),
        ];
    }

}
