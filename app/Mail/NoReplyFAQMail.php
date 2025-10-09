<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NoReplyFAQMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;


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

    public function headers(): Headers
    {
        return new Headers(text: [
            // RFC 3834 (Auto-Reply)
            'Auto-Submitted' => 'auto-replied',
            'X-Auto-Response-Suppress' => 'All',
        ]);
    }

    protected function viewName(): string
    {
        return 'emails.no_reply_faq';
    }
}
