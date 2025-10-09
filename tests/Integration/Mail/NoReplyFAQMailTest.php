<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Mail\NoReplyFAQMail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;
use Tests\TestCase;

class NoReplyFAQMailTest extends TestCase
{
    public function testEnvelopeProvidesStaticSubject(): void
    {
        $mail = new NoReplyFAQMail();

        $envelope = $mail->envelope();

        $this->assertSame('Automatische Antwort – bitte nicht direkt antworten', $envelope->subject);
    }

    public function testHeadersIndicateAutoResponder(): void
    {
        $mail = new NoReplyFAQMail();

        $headers = $mail->headers();

        $this->assertInstanceOf(Headers::class, $headers);
        $this->assertSame('auto-replied', $headers->text['Auto-Submitted']);
        $this->assertSame('All', $headers->text['X-Auto-Response-Suppress']);
    }

    public function testContentUsesFaqTemplate(): void
    {
        $mail = new NoReplyFAQMail();

        $content = $mail->content();

        $this->assertInstanceOf(Content::class, $content);
        $this->assertSame('emails.no_reply_faq', $content->view);
        $this->assertSame(
            'Automatische Antwort – bitte nicht direkt antworten',
            $content->with['subject']
        );
    }
}
