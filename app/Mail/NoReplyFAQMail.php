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
        $headers = parent::headers();
        $headers->text(['Auto-Submitted' => 'auto-replied']);
        return $headers;
    }

    protected function viewName(): string
    {
        return 'emails.no_reply_faq';
    }
}
