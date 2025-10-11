<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mail\Scanner\Handlers;

use App\Enum\MailStatus;
use App\Models\MailLog;
use App\Services\Mail\Scanner\Handlers\BounceHandler;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\DatabaseTestCase;
use Webklex\PHPIMAP\Message;

class BounceHandlerTest extends DatabaseTestCase
{
    public function testMatchesReturnsTrueForKnownBounceSubjects(): void
    {
        $subject = Mockery::mock();
        $subject->shouldReceive('toString')->andReturn('Mail Delivery Failed: Undeliverable');

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getSubject')->andReturn($subject);

        $handler = new BounceHandler();

        $this->assertTrue($handler->matches($message));
    }

    public function testMatchesReturnsFalseForOtherSubjects(): void
    {
        $subject = Mockery::mock();
        $subject->shouldReceive('toString')->andReturn('Regular update');

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getSubject')->andReturn($subject);

        $handler = new BounceHandler();

        $this->assertFalse($handler->matches($message));
    }

    public function testHandleUpdatesMailLogWhenOriginalMessageFound(): void
    {
        $log = MailLog::create([
            'message_id' => 'original-message-id@example.com',
            'to' => 'user@example.com',
            'subject' => 'Initial Mail',
            'status' => MailStatus::Sent,
        ]);

        $body = "Delivery failed\nMessage-ID: <original-message-id@example.com>\nAdditional info";

        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getTextBody')->andReturn($body);

        Log::shouldReceive('info')
            ->once()
            ->with('Bounce for original-message-id@example.com');

        $handler = new BounceHandler();
        $handler->handle($message);

        $log->refresh();

        $this->assertSame(MailStatus::Bounced, $log->status);
        $this->assertNotNull($log->bounced_at);
        $this->assertArrayHasKey('bounce_excerpt', $log->meta);
        $this->assertSame(substr($body, 0, 400), $log->meta['bounce_excerpt']);
    }

    public function testHandleSkipsWhenMailLogCannotBeResolved(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getTextBody')->andReturn('No matching id here');

        Log::shouldReceive('info')->never();

        $handler = new BounceHandler();
        $handler->handle($message);

        $this->assertDatabaseCount('mail_logs', 0);
    }
}
