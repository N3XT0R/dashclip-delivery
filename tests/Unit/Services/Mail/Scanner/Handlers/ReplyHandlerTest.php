<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mail\Scanner\Handlers;

use App\Enum\MailStatus;
use App\Mail\NoReplyFAQMail;
use App\Models\MailLog;
use App\Services\Mail\Scanner\Handlers\ReplyHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\DatabaseTestCase;
use Webklex\PHPIMAP\Message;

class ReplyHandlerTest extends DatabaseTestCase
{

    protected ReplyHandler $replyHandler;


    protected function setUp(): void
    {
        parent::setUp();
        $this->replyHandler = $this->app->make(ReplyHandler::class);
    }


    public function testMatchesReturnsTrueWhenInReplyToHeaderPresent(): void
    {
        $inReplyTo = Mockery::mock();

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getInReplyTo')->andReturn($inReplyTo);

        $handler = $this->app->make(ReplyHandler::class);

        $this->assertTrue($handler->matches($message));
    }

    public function testMatchesReturnsFalseWhenNoReplyHeaderPresent(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getInReplyTo')->andReturn(null);

        $this->assertFalse($this->replyHandler->matches($message));
    }

    public function testHandleQueuesAutoResponderAndUpdatesMailLog(): void
    {
        Mail::fake();

        $log = MailLog::create([
            'message_id' => '<thread-123@example.com>',
            'to' => 'user@example.com',
            'subject' => 'Support Request',
            'status' => MailStatus::Sent,
        ]);

        $from = new class('customer@example.com') {
            public function __construct(public string $mail)
            {
            }
        };

        $inReplyTo = Mockery::mock();
        $inReplyTo->shouldReceive('toString')->andReturn('thread-123@example.com');

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getInReplyTo')->andReturn($inReplyTo);
        $message->shouldReceive('getFrom')->andReturn([$from]);

        Log::shouldReceive('info')
            ->once()
            ->with('Auto-reply sent to customer@example.com', ['to' => 'customer@example.com']);

        $this->replyHandler->handle($message);

        $log->refresh();

        Mail::assertQueued(NoReplyFAQMail::class, fn($mail) => true);

        $this->assertSame(MailStatus::Replied, $log->status);
        $this->assertNotNull($log->replied_at);
    }

    public function testHandleDoesNothingWhenNoMatchingLogExists(): void
    {
        Mail::fake();

        $from = new class('customer@example.com') {
            public function __construct(public string $mail)
            {
            }
        };

        $inReplyTo = Mockery::mock();
        $inReplyTo->shouldReceive('toString')->andReturn('missing-thread@example.com');

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getInReplyTo')->andReturn($inReplyTo);
        $message->shouldReceive('getFrom')->andReturn([$from]);

        Log::shouldReceive('info')->never();

        $this->replyHandler->handle($message);

        Mail::assertNothingQueued();
        $this->assertDatabaseCount('mail_logs', 0);
    }

    public function testHandleSkipsWhenAlreadyReplied(): void
    {
        Mail::fake();

        MailLog::create([
            'message_id' => '<answered@example.com>',
            'to' => 'user@example.com',
            'subject' => 'Support Request',
            'status' => MailStatus::Replied,
        ]);

        $from = new class('customer@example.com') {
            public function __construct(public string $mail)
            {
            }
        };

        $inReplyTo = Mockery::mock();
        $inReplyTo->shouldReceive('toString')->andReturn('answered@example.com');

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getInReplyTo')->andReturn($inReplyTo);
        $message->shouldReceive('getFrom')->andReturn([$from]);

        Log::shouldReceive('info')->never();

        $this->replyHandler->handle($message);

        Mail::assertNothingQueued();
        $this->assertSame(1, MailLog::count());
    }
}
