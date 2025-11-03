<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mail\Scanner\Handlers;

use App\Enum\MailDirection;
use App\Enum\MailStatus;
use App\Repository\MailRepository;
use App\Services\Mail\Scanner\Handlers\InboundHandler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\DatabaseTestCase;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Message;

class InboundHandlerTest extends DatabaseTestCase
{
    public function testMatchesReturnsTrueForInboxFolder(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFolderPath')->once()->andReturn('INBOX');

        $repository = Mockery::mock(MailRepository::class);
        $handler = new InboundHandler($repository);

        $this->assertTrue($handler->matches($message));
    }

    public function testMatchesReturnsFalseForOtherFolders(): void
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getFolderPath')->once()->andReturn('Sent');

        $repository = Mockery::mock(MailRepository::class);
        $handler = new InboundHandler($repository);

        $this->assertFalse($handler->matches($message));
    }

    public function testHandleCreatesNewMailEntry(): void
    {
        $repository = Mockery::mock(MailRepository::class);
        $message = Mockery::mock(Message::class);

        // Header::get() must return Attribute, not null
        $attribute = Mockery::mock(Attribute::class);
        $attribute->shouldReceive('toDate')->andReturn(Carbon::parse('2025-10-31 12:00:00'));

        $header = Mockery::mock(Header::class);
        $header->shouldReceive('get')->with('Date')->andReturn($attribute);
        $header->shouldReceive('getAttributes')->andReturn(['Header-Key' => 'Header-Value']);

        $message->shouldReceive('getHeader')->andReturn($header);
        $message->shouldReceive('getDate->toDate')->andReturn(Carbon::parse('2025-10-31 12:00:00'));
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'user@example.com']]);
        $message->shouldReceive('getTo')->andReturn([(object)['mail' => 'receiver@example.com']]);
        $message->shouldReceive('getSubject->toString')->andReturn('Test inbound mail');
        $message->shouldReceive('getMessageId->toString')->andReturn('msg-123@example.com');
        $message->shouldReceive('getRawBody')->andReturn('Raw message body');

        $repository->shouldReceive('existsByMessageId')
            ->once()
            ->with('msg-123@example.com')
            ->andReturn(false);

        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['message_id'] === 'msg-123@example.com'
                    && $data['from'] === 'user@example.com'
                    && $data['subject'] === 'Test inbound mail'
                    && $data['direction'] === MailDirection::INBOUND
                    && $data['status'] === MailStatus::Received
                    && isset($data['meta']['headers'], $data['meta']['content']);
            }));

        Log::shouldReceive('info')
            ->once()
            ->with('Inbound mail stored', Mockery::subset([
                'subject' => 'Test inbound mail',
                'from' => 'user@example.com',
            ]));

        $handler = new InboundHandler($repository);
        $handler->handle($message);
    }

    public function testHandleSkipsAlreadyProcessedMail(): void
    {
        $repository = Mockery::mock(MailRepository::class);
        $message = Mockery::mock(Message::class);

        // Must mock all methods the handler touches
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'user@example.com']]);
        $message->shouldReceive('getTo')->andReturn([(object)['mail' => 'receiver@example.com']]);
        $message->shouldReceive('getSubject->toString')->andReturn('Duplicate Mail');
        $message->shouldReceive('getMessageId->toString')->andReturn('msg-duplicate@example.com');
        $message->shouldReceive('getHeader')->andReturnNull();
        $message->shouldReceive('getDate->toDate')->andReturn(Carbon::now());
        $message->shouldReceive('getRawBody')->andReturn('Irrelevant body');

        $repository->shouldReceive('existsByMessageId')
            ->once()
            ->with('msg-duplicate@example.com')
            ->andReturn(true);

        Log::shouldReceive('info')
            ->once()
            ->with('Mail already processed: msg-duplicate@example.com');

        $repository->shouldReceive('create')->never();

        $handler = new InboundHandler($repository);
        $handler->handle($message);
    }
}
