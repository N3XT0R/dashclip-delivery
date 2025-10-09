<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Mail\AbstractLoggedMail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;
use Tests\TestCase;

class AbstractLoggedMailTest extends TestCase
{
    public function testHeadersIncludeGeneratedMessageIdAndDefaultAutoSubmitted(): void
    {
        config(['mail.from.address' => 'mailer@example.com']);

        $mail = new class extends AbstractLoggedMail {
            protected string $subjectLine = 'Integration Subject';

            protected function viewName(): string
            {
                return 'emails.stub';
            }

            protected function viewData(): array
            {
                return ['foo' => 'bar'];
            }
        };

        $headers = $mail->headers();

        $this->assertInstanceOf(Headers::class, $headers);
        $this->assertNotEmpty($headers->messageId);
        $this->assertStringContainsString('@example.com', $headers->messageId);
        $this->assertStringNotContainsString('<', $headers->messageId);
        $this->assertSame('auto-generated', $headers->text['Auto-Submitted']);
        $this->assertArrayHasKey('X-System-Meta', $headers->text);
        $this->assertArrayNotHasKey('X-Auto-Response-Suppress', $headers->text);
    }

    public function testContentIncludesSubjectAndViewData(): void
    {
        config(['mail.from.address' => 'mailer@example.com']);

        $mail = new class extends AbstractLoggedMail {
            protected string $subjectLine = 'Integration Subject';

            protected function viewName(): string
            {
                return 'emails.stub';
            }

            protected function viewData(): array
            {
                return ['foo' => 'value'];
            }
        };

        $content = $mail->content();

        $this->assertInstanceOf(Content::class, $content);
        $this->assertSame('emails.stub', $content->view);
        $this->assertSame('Integration Subject', $content->with['subject']);
        $this->assertSame('value', $content->with['foo']);
    }

    public function testAutoResponderHeadersOverrideDefaultsAndUseFallbackDomain(): void
    {
        config([
            'mail.from.address' => null,
            'app.url' => 'https://mailer.test',
        ]);

        $mail = new class extends AbstractLoggedMail {
            protected string $subjectLine = 'Auto Subject';
            protected bool $isAutoResponder = true;

            protected function viewName(): string
            {
                return 'emails.stub';
            }
        };

        $headers = $mail->headers();

        $this->assertSame('auto-replied', $headers->text['Auto-Submitted']);
        $this->assertSame('All', $headers->text['X-Auto-Response-Suppress']);
        $this->assertStringContainsString('@mailer.test', $headers->messageId);
        $this->assertStringNotContainsString('<', $headers->messageId);
    }
}
