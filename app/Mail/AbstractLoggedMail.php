<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

abstract class AbstractLoggedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected string $subjectLine = '';

    abstract protected function viewName(): string;

    protected function viewData(): array
    {
        return [];
    }


    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName(),
            with: array_merge($this->viewData(), [
                'subject' => $this->envelope()->subject,
            ]),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->generateMessageId(),
            text: [
                'X-App-Message-ID' => (string)Str::uuid(),
                // RFC 3834-konform (Auto-Reply)
                'Auto-Submitted' => 'auto-replied',
                // verhindert Schleifen, v. a. bei Outlook / Exchange
                'X-Auto-Response-Suppress' => 'All',
                // Easter egg header for the curious ones
                'X-Egg' => 'If you are reading this, you are probably too curious',
            ],
        );
    }

    protected function generateMessageId(): string
    {
        // Try to determine the actual sender domain first
        $mailFrom = config('mail.from.address');
        $domain = $mailFrom && str_contains($mailFrom, '@')
            ? substr(strrchr($mailFrom, '@'), 1)
            : (parse_url(config('app.url'), PHP_URL_HOST) ?? gethostname());

        // RFC-compliant unique part for the Message-ID local section
        $unique = bin2hex(random_bytes(16));

        return sprintf('<%s@%s>', $unique, $domain);
    }


    public function attachments(): array
    {
        return [];
    }
}