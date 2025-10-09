<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NoReplyFAQMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;

    protected bool $isAutoResponder = true;


    /**
     * Create a new message instance.
     */
    public function __construct()
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Automatische Antwort – bitte nicht direkt antworten',
        );
    }

    protected function viewName(): string
    {
        return 'emails.no_reply_faq';
    }
}
