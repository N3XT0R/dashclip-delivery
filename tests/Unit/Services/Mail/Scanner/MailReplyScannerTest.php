<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mail\Scanner;

use App\Services\Mail\Scanner\Contracts\MessageStrategyInterface;
use App\Services\Mail\Scanner\Contracts\MoveToFolderInterface;
use App\Services\Mail\Scanner\MailReplyScanner;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Client as ClientAlias;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;

class MailReplyScannerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItSkipsAutoSubmittedMessagesAndMarksThemSeen(): void
    {
        $handler = new FakeHandler();

        $scanner = new MailReplyScanner([$handler]);

        $client = Mockery::mock(ClientAlias::class);
        Client::shouldReceive('account')->once()->with(null)->andReturn($client);
        $client->shouldReceive('connect')->once();

        $inbox = Mockery::mock(Folder::class);
        $query = Mockery::mock(WhereQuery::class);
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($inbox);
        $inbox->shouldReceive('messages')->andReturn($query);
        $query->shouldReceive('unseen')->andReturn($query);

        $message = $this->createMessageMock(autoSubmitted: true);
        $message->shouldReceive('setFlag')->with('Seen')->once();
        $query->shouldReceive('get')->andReturn(new MessageCollection([$message]));

        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::on(fn($message) => str_contains($message, 'was ignored')));
        Log::shouldReceive('error')->never();

        $scanner->scan();

        $this->assertSame(0, $handler->matchesCalled);
        $this->assertSame(0, $handler->handledCalled);
    }

    public function testItDispatchesMatchingHandlersMovesMessageAndCreatesFolders(): void
    {
        config()->set('app.debug', false);

        $handler = new FakeHandler();

        $scanner = new MailReplyScanner([$handler]);

        $client = Mockery::mock(ClientAlias::class);
        Client::shouldReceive('account')->once()->with(null)->andReturn($client);
        $client->shouldReceive('connect')->once();

        $inbox = Mockery::mock(Folder::class);
        $query = Mockery::mock(WhereQuery::class);
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($inbox);
        $inbox->shouldReceive('messages')->andReturn($query);
        $query->shouldReceive('unseen')->andReturn($query);

        $message = $this->createMessageMock();
        $message->shouldReceive('setFlag')->with('Seen')->once();
        $query->shouldReceive('get')->andReturn(new MessageCollection([$message]));

        $client->shouldReceive('getFolder')->with('Processed/Test')->andReturn(null);
        $client->shouldReceive('createFolder')->once()->with('Processed/Test');

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->never();

        $message->shouldReceive('move')->once()->with('Processed/Test');

        $scanner->scan();

        $this->assertSame(1, $handler->matchesCalled);
        $this->assertSame(1, $handler->handledCalled);
    }

    public function testItSkipsMovingMessagesWhenInDebugMode(): void
    {
        config()->set('app.debug', true);

        $handler = new FakeHandler();

        $scanner = new MailReplyScanner([$handler]);

        $client = Mockery::mock(ClientAlias::class);
        Client::shouldReceive('account')->once()->with(null)->andReturn($client);
        $client->shouldReceive('connect')->once();

        $inbox = Mockery::mock(Folder::class);
        $query = Mockery::mock(WhereQuery::class);
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($inbox);
        $inbox->shouldReceive('messages')->andReturn($query);
        $query->shouldReceive('unseen')->andReturn($query);

        $message = $this->createMessageMock();
        $message->shouldReceive('setFlag')->with('Seen')->once();
        $query->shouldReceive('get')->andReturn(new MessageCollection([$message]));

        $client->shouldReceive('getFolder')->with('Processed/Test')->never();
        $client->shouldReceive('createFolder')->never();

        $message->shouldReceive('move')->never();
        Log::shouldReceive('error')->never();

        $scanner->scan();

        $this->assertSame(1, $handler->matchesCalled);
        $this->assertSame(1, $handler->handledCalled);
    }

    public function testItFlagsMessagesWhenHandlerThrows(): void
    {
        config()->set('app.debug', false);

        $handler = new FakeHandler(shouldMatch: true, throwable: new \RuntimeException('failed'));

        $scanner = new MailReplyScanner([$handler]);

        $client = Mockery::mock(ClientAlias::class);
        Client::shouldReceive('account')->once()->with(null)->andReturn($client);
        $client->shouldReceive('connect')->once();

        $inbox = Mockery::mock(Folder::class);
        $query = Mockery::mock(WhereQuery::class);
        $client->shouldReceive('getFolder')->with('INBOX')->andReturn($inbox);
        $inbox->shouldReceive('messages')->andReturn($query);
        $query->shouldReceive('unseen')->andReturn($query);

        $message = $this->createMessageMock();
        $query->shouldReceive('get')->andReturn(new MessageCollection([$message]));

        $client->shouldReceive('getFolder')->with('Processed/Test')->never();
        $client->shouldReceive('createFolder')->never();

        Log::shouldReceive('error')
            ->once()
            ->with('IMAP processing failed', Mockery::type('array'));

        $message->shouldReceive('setFlag')->with('Flagged')->once();
        $message->shouldReceive('setFlag')->with('Seen')->never();

        $scanner->scan();

        $this->assertSame(1, $handler->matchesCalled);
        $this->assertSame(1, $handler->handledCalled);
    }

    private function createMessageMock(bool $autoSubmitted = false)
    {
        $message = Mockery::mock(Message::class);

        $headers = Mockery::mock(\Webklex\PHPIMAP\Header::class);
        $headers->shouldReceive('has')->with('Auto-Submitted')->andReturn($autoSubmitted);
        $message->shouldReceive('getHeader')->andReturn($headers);

        $messageId = Mockery::mock();
        $messageId->shouldReceive('toString')->andReturn('msg-1');
        $message->shouldReceive('getMessageId')->andReturn($messageId);

        return $message;
    }
}

class FakeHandler implements MessageStrategyInterface, MoveToFolderInterface
{
    public int $matchesCalled = 0;
    public int $handledCalled = 0;

    public function __construct(
        public bool $shouldMatch = true,
        public ?\Throwable $throwable = null
    ) {
    }

    public function matches(Message $message): bool
    {
        $this->matchesCalled++;

        return $this->shouldMatch;
    }

    public function handle(Message $message): void
    {
        if ($this->throwable) {
            $this->handledCalled++;
            throw $this->throwable;
        }

        $this->handledCalled++;
    }

    public function getMoveToFolderPath(): string
    {
        return 'Processed/Test';
    }
}
