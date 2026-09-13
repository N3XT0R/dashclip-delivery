<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mail\Scanner\Handlers;

use App\Models\MailLog;
use App\Repository\MailRepository;
use App\Services\Mail\Scanner\Handlers\InboundHandler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\DatabaseTestCase;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Message;

/**
 * The stored body must be readable. Before this, the handler kept the raw
 * message, so every inbound record held MIME boundaries or base64 instead of
 * the actual text.
 */
final class InboundHandlerContentTest extends DatabaseTestCase
{
    public function testTheDecodedHtmlBodyIsStoredInsteadOfTheRawMessage(): void
    {
        $captured = $this->handleMessage(
            html: '<p>Danke für deine Einsendung</p>',
            text: 'Danke fuer deine Einsendung',
            raw: "--boundary\r\nContent-Transfer-Encoding: base64\r\n\r\nPHA+RGFua2U8L3A+\r\n--boundary--",
        );

        self::assertSame('<p>Danke für deine Einsendung</p>', $captured['meta']['content']);
        self::assertStringNotContainsString('--boundary', $captured['meta']['content']);
    }

    public function testThePlainTextBodyIsUsedWhenNoHtmlPartExists(): void
    {
        $captured = $this->handleMessage(
            html: '',
            text: 'Nur Text, kein HTML',
            raw: 'Content-Transfer-Encoding: quoted-printable',
        );

        self::assertSame('Nur Text, kein HTML', $captured['meta']['content']);
    }

    public function testTheRawMessageIsKeptOnlyWhenNothingElseCanBeDecoded(): void
    {
        $captured = $this->handleMessage(html: '', text: '', raw: 'unparseable payload');

        self::assertSame('unparseable payload', $captured['meta']['content']);
    }

    public function testTheStoredFormatIsRecordedSoTheViewerKnowsHowToRenderIt(): void
    {
        $html = $this->handleMessage(html: '<p>hallo</p>', text: 'hallo', raw: 'raw');
        $plain = $this->handleMessage(html: '', text: 'hallo', raw: 'raw');

        self::assertSame('html', $html['meta']['content_format']);
        self::assertSame('text', $plain['meta']['content_format']);
    }

    /**
     * @return array<string, mixed>
     */
    private function handleMessage(string $html, string $text, string $raw): array
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getHeader')->andReturnNull();
        $message->shouldReceive('getDate->toDate')->andReturn(Carbon::parse('2026-09-13 12:00:00'));
        $message->shouldReceive('getFrom')->andReturn([(object)['mail' => 'sender@example.com']]);
        $message->shouldReceive('getTo')->andReturn(new Attribute('to', 'inbox@example.com'));
        $message->shouldReceive('getSubject->toString')->andReturn('Betreff');
        $message->shouldReceive('getMessageId->toString')->andReturn('msg-'.uniqid().'@example.com');
        $message->shouldReceive('getHTMLBody')->andReturn($html);
        $message->shouldReceive('getTextBody')->andReturn($text);
        $message->shouldReceive('getRawBody')->andReturn($raw);

        $captured = [];
        $repository = Mockery::mock(MailRepository::class);
        $repository->shouldReceive('existsByMessageId')->andReturn(false);
        $repository->shouldReceive('create')->once()
            ->andReturnUsing(static function (array $data) use (&$captured): MailLog {
                $captured = $data;

                return new MailLog();
            });

        Log::shouldReceive('info');

        (new InboundHandler($repository))->handle($message);

        return $captured;
    }
}
