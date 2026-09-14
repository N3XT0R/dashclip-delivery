<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enum\MailDirection;
use App\Models\MailLog;
use Tests\DatabaseTestCase;

/**
 * Inbound entries written before the reading fix hold the raw message. They can
 * be repaired in place, because the raw payload carries everything needed.
 */
final class RepairInboundMailContentCommandTest extends DatabaseTestCase
{
    public function testABase64BodyIsDecodedIntoReadableText(): void
    {
        $log = $this->inbound(
            "Content-Type: text/html; charset=utf-8\r\n"
            ."Content-Transfer-Encoding: base64\r\n\r\n"
            .chunk_split(base64_encode('<div>Danke für deine Einsendung</div>'), 76, "\r\n"),
        );

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        $log->refresh();
        self::assertSame('<div>Danke für deine Einsendung</div>', trim((string)$log->meta['content']));
        self::assertSame('html', $log->meta['content_format']);
    }

    public function testAQuotedPrintableBodyIsDecoded(): void
    {
        $log = $this->inbound(
            "Content-Type: text/plain; charset=utf-8\r\n"
            ."Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            ."Gr=C3=BC=C3=9Fe aus M=C3=BCnchen",
        );

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        self::assertSame('Grüße aus München', trim((string)$log->refresh()->meta['content']));
        self::assertSame('text', $log->meta['content_format']);
    }

    public function testAMultipartMessagePrefersItsHtmlPart(): void
    {
        $log = $this->inbound(
            "Content-Type: multipart/alternative; boundary=\"abc\"\r\n\r\n"
            ."--abc\r\nContent-Type: text/plain; charset=utf-8\r\n\r\nNur Text\r\n"
            ."--abc\r\nContent-Type: text/html; charset=utf-8\r\n\r\n<p>Als HTML</p>\r\n"
            ."--abc--",
        );

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        self::assertSame('<p>Als HTML</p>', trim((string)$log->refresh()->meta['content']));
        self::assertSame('html', $log->meta['content_format']);
    }

    public function testOutboundEntriesAreLeftUntouched(): void
    {
        $log = MailLog::factory()->create([
            'direction' => MailDirection::OUTBOUND,
            'meta' => ['headers' => [], 'content' => '<!DOCTYPE html><html><body>ok</body></html>'],
        ]);

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        self::assertSame('<!DOCTYPE html><html><body>ok</body></html>', $log->refresh()->meta['content']);
    }

    public function testAnAlreadyRepairedEntryIsNotTouchedAgain(): void
    {
        $log = MailLog::factory()->create([
            'direction' => MailDirection::INBOUND,
            'meta' => ['headers' => [], 'content' => '<p>schon gut</p>', 'content_format' => 'html'],
        ]);

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        self::assertSame('<p>schon gut</p>', $log->refresh()->meta['content']);
    }

    public function testABareBase64BlockWithoutAnyHeadersIsDecoded(): void
    {
        // Real inbound entries arrive like this: no headers at all, just the
        // encoded payload, so there is no Content-Transfer-Encoding to read.
        $log = $this->inbound(chunk_split(base64_encode('<div dir="ltr">Danke für deine Einsendung</div>'), 76, "\r\n"));

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        $log->refresh();
        self::assertSame('<div dir="ltr">Danke für deine Einsendung</div>', trim((string)$log->meta['content']));
        self::assertSame('html', $log->meta['content_format']);
    }

    public function testABoundaryAnnouncedOnlyInThePreambleIsStillFound(): void
    {
        // Bounce notifications start with a human preamble and never declare the
        // boundary in a header, so it has to be recognised from the delimiter.
        $log = $this->inbound(
            "This is a MIME-encapsulated message.\r\n\r\n"
            ."--zz.1776066740/mta-08.example.com\r\n"
            ."Content-Description: Notification\r\n"
            ."Content-Type: text/plain; charset=utf-8\r\n"
            ."Content-Transfer-Encoding: 8bit\r\n\r\n"
            ."This is the mail system at host example.com\r\n"
            ."--zz.1776066740/mta-08.example.com--",
        );

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        $log->refresh();
        self::assertSame('This is the mail system at host example.com', trim((string)$log->meta['content']));
        self::assertSame('text', $log->meta['content_format']);
    }

    public function testAnEntryThatCannotBeDecodedKeepsItsContentAndStaysUnmarked(): void
    {
        $log = $this->inbound('nothing decodable here at all');

        $this->artisan('mail:repair-inbound-content')->assertSuccessful();

        $log->refresh();
        self::assertSame('nothing decodable here at all', $log->meta['content']);
        self::assertArrayNotHasKey('content_format', $log->meta);
    }

    private function inbound(string $raw): MailLog
    {
        return MailLog::factory()->create([
            'direction' => MailDirection::INBOUND,
            'meta' => ['headers' => [], 'content' => $raw],
        ]);
    }
}
