<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Channel;
use Illuminate\Mail\Mailables\Envelope;

class ChannelWelcomeMail extends AbstractLoggedMail
{

    public function __construct(public Channel $channel)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Willkommen beim wöchentlichen Video-Versand',
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
            'approveUrl' => $this->channel->getApprovalToken(),
        ];
    }

}