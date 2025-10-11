<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enum\MailStatus;
use App\Listeners\LogSentMail;
use App\Models\MailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage as LaravelSentMessage;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\DatabaseTestCase;

class LogSentMailTest extends DatabaseTestCase
{
    private function createEventFromEmail(Email $email, array $data = []): MessageSent
    {
        $from = $email->getFrom()[0] ?? new Address('no-reply@example.com');

        $envelope = new Envelope($from, $email->getTo());

        $sent = new SymfonySentMessage($email, $envelope);

        return new MessageSent(new LaravelSentMessage($sent), $data);
    }

    public function testItPersistsMailLogWithHeadersAndContent(): void
    {
        $listener = new LogSentMail();

        $email = (new Email())
            ->from('noreply@example.com')
            ->to('user@example.com')
            ->subject('Welcome to Dashclip')
            ->html('<p>Hello there!</p>');

        $email->getHeaders()->addIdHeader('Message-ID', 'message-123@example.com');
        $email->getHeaders()->addTextHeader('X-App-Message-ID', 'internal-456');

        $event = $this->createEventFromEmail($email);

        $listener->handle($event);

        $log = MailLog::firstOrFail();

        $this->assertSame('<message-123@example.com>', $log->message_id);
        $this->assertSame('internal-456', $log->internal_id);
        $this->assertSame('user@example.com', $log->to);
        $this->assertSame('Welcome to Dashclip', $log->subject);
        $this->assertSame(MailStatus::Sent, $log->status);
        $this->assertSame('<p>Hello there!</p>', $log->meta['content']);
        $this->assertContains('Message-ID: <message-123@example.com>', $log->meta['headers']);
    }

    public function testItFallsBackToEventHeadersWhenMessageLacksIds(): void
    {
        $listener = new LogSentMail();

        $email = (new Email())
            ->from('noreply@example.com')
            ->to('another@example.com')
            ->subject('Fallback IDs')
            ->html('<p>Body</p>');

        $event = $this->createEventFromEmail($email, [
            'headers' => [
                'Message-ID' => 'event-message-id',
                'X-App-Message-ID' => 'event-internal-id',
            ],
        ]);

        $listener->handle($event);

        $log = MailLog::where('to', 'another@example.com')->firstOrFail();

        $this->assertSame('event-message-id', $log->message_id);
        $this->assertSame('event-internal-id', $log->internal_id);
    }

    public function testItSkipsLoggingWhenRecipientMissing(): void
    {
        $listener = new LogSentMail();

        $headers = new Headers();

        $address = new class {
            public function getAddress(): ?string
            {
                return null;
            }
        };

        $message = Mockery::mock(Email::class);
        $message->shouldReceive('getHeaders')->andReturn($headers);
        $message->shouldReceive('getTo')->andReturn([$address]);
        $message->shouldReceive('getSubject')->andReturn('Subjectless');
        $message->shouldReceive('getHtmlBody')->andReturn('<p>Empty</p>');

        $symfonySent = Mockery::mock(SymfonySentMessage::class);
        $symfonySent->shouldReceive('getOriginalMessage')->andReturn($message);

        $event = new MessageSent(new LaravelSentMessage($symfonySent), ['headers' => []]);

        Log::shouldReceive('warning')
            ->once()
            ->with('MessageSent without recipient', Mockery::type('array'));

        $listener->handle($event);

        $this->assertDatabaseCount('mail_logs', 0);
    }
}
