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

    protected bool $isAutoResponder = false;

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
        $header = [
            'X-App-Message-ID' => (string)Str::uuid(),
            // RFC 3834-konform (Auto-Reply)
            'Auto-Submitted' => 'auto-replied',
            // verhindert Schleifen, v. a. bei Outlook / Exchange
            'X-Auto-Response-Suppress' => 'All',
            // Easter egg header for the curious ones
            'X-System-Meta' => 'trace-id='.bin2hex(random_bytes(4)).'; note="If you are reading this, you are way too curious"',
        ];

        if ($this->isAutoResponder) {
            // RFC 3834-konform (Auto-Reply)
            $header['Auto-Submitted'] = 'auto-replied';
            // verhindert Schleifen, v. a. bei Outlook / Exchange
            $header['X-Auto-Response-Suppress'] = 'All';
        } else {
            // RFC 3834-konform (Auto-Reply)
            $header['Auto-Submitted'] = 'auto-generated';
        }
        return new Headers(
            messageId: $this->generateMessageId(),
            text: $header,
        );
    }

    protected function generateMessageId(): string
    {
        // Determine domain
        $mailFrom = config('mail.from.address');
        $domain = $mailFrom && str_contains($mailFrom, '@')
            ? substr(strrchr($mailFrom, '@'), 1)
            : (parse_url(config('app.url'), PHP_URL_HOST) ?? gethostname());

        // RFC-compliant unique part for the Message-ID local section
        $unique = bin2hex(random_bytes(16));

        // Important: return without angle brackets — Symfony adds them automatically
        return sprintf('%s@%s', $unique, $domain);
    }


    public function attachments(): array
    {
        return [];
    }
}