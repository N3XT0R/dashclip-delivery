<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Resources;

use App\Enum\MailDirection;
use App\Models\MailLog;
use App\Services\Mail\MailContentPresenter;
use Tests\DatabaseTestCase;

/**
 * The viewer must not place foreign mail markup into the admin document.
 * Inbound content is written by whoever sends the mail, so rendering it inline
 * hands that sender the admin page.
 */
final class MailLogContentViewTest extends DatabaseTestCase
{
    private MailContentPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = app(MailContentPresenter::class);
    }

    public function testHtmlMailIsRenderedInsideAnIsolatedSandboxedFrame(): void
    {
        $log = $this->log('<!DOCTYPE html><html><body><p>Hallo Welt</p></body></html>', 'html');

        $markup = $this->presenter->render($log)->toHtml();

        self::assertStringContainsString('<iframe', $markup);
        self::assertMatchesRegularExpression('/sandbox="\s*"/', $markup);
        self::assertStringContainsString('srcdoc=', $markup);
    }

    public function testScriptFromAForeignMailNeverReachesTheAdminDocument(): void
    {
        $log = $this->log('<p>hi</p><script>window.stolen=1</script>', 'html');

        $markup = $this->presenter->render($log)->toHtml();

        self::assertStringNotContainsString('<script>window.stolen=1</script>', $markup);
        self::assertStringContainsString('&lt;script&gt;', $markup);
    }

    public function testRemoteImagesAreBlockedButKeptForLaterInspection(): void
    {
        $log = $this->log('<img src="https://tracker.example.com/pixel.gif">', 'html');

        $document = $this->innerDocument($log);

        // A plain "not contains" would pass on data-blocked-src too, since that
        // attribute name ends in src. Match the attribute boundary instead.
        self::assertDoesNotMatchRegularExpression('/\ssrc=["\']https?:/i', $document);
        self::assertStringContainsString('data-blocked-src="https://tracker.example.com/pixel.gif"', $document);
    }

    public function testPlainTextMailIsEscapedAndNotTreatedAsMarkup(): void
    {
        $log = $this->log("Zeile eins\nZeile <b>zwei</b>", 'text');

        $document = $this->innerDocument($log);

        self::assertStringContainsString('&lt;b&gt;zwei&lt;/b&gt;', $document);
        self::assertStringNotContainsString('<b>zwei</b>', $document);
    }

    public function testAnEmptyBodyReportsThatNoContentExists(): void
    {
        $log = $this->log('   ', 'text');

        self::assertStringContainsString(
            __('filament.admin.messages.empty_email_content'),
            $this->presenter->render($log)->toHtml(),
        );
    }

    /**
     * Decode the document the sandboxed frame will actually show, so the
     * assertions describe what the viewer sees rather than escaping layers.
     *
     * @param MailLog $log
     * @return string
     */
    private function innerDocument(MailLog $log): string
    {
        $markup = $this->presenter->render($log)->toHtml();
        self::assertSame(1, preg_match('/srcdoc="([^"]*)"/', $markup, $match));

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }

    private function log(string $content, string $format): MailLog
    {
        return MailLog::factory()->create([
            'direction' => MailDirection::INBOUND,
            'meta' => ['headers' => [], 'content' => $content, 'content_format' => $format],
        ]);
    }
}
