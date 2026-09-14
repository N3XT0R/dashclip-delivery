<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\MailLog;
use Illuminate\Support\HtmlString;

/**
 * Render a logged mail body for the administration area.
 *
 * Mail bodies are foreign documents. An outbound mail is a complete HTML
 * document that cannot live inside a div, and an inbound body is written by
 * whoever sent the mail. Both are therefore rendered inside a sandboxed frame
 * rather than placed into the admin document.
 */
final class MailContentPresenter
{
    private const FRAME_STYLE = 'width:100%;height:70vh;border:0;border-radius:8px;background:#ffffff;';

    /**
     * Build the isolated markup for the stored body of a mail log entry.
     *
     * @param MailLog $log
     * @return HtmlString
     */
    public function render(MailLog $log): HtmlString
    {
        $content = (string)($log->meta['content'] ?? '');

        if (trim($content) === '') {
            return new HtmlString(sprintf('<em>%s</em>', e(__('filament.admin.messages.empty_email_content'))));
        }

        $document = $this->isHtml($log, $content)
            ? $this->blockRemoteImages($content)
            : sprintf('<pre style="white-space:pre-wrap;word-break:break-word;">%s</pre>', e($content));

        return new HtmlString(sprintf(
            '<iframe sandbox="" referrerpolicy="no-referrer" style="%s" srcdoc="%s"></iframe>',
            self::FRAME_STYLE,
            e($this->wrap($document)),
        ));
    }

    /**
     * Decide whether the stored body should be treated as markup.
     *
     * Prefer the format recorded while reading the mail and fall back to
     * sniffing, because entries written before that field existed lack it.
     *
     * @param MailLog $log
     * @param string $content
     * @return bool
     */
    private function isHtml(MailLog $log, string $content): bool
    {
        $format = $log->meta['content_format'] ?? null;

        if ($format === 'html') {
            return true;
        }

        if ($format === 'text') {
            return false;
        }

        return str_contains($content, '<html') || (bool)preg_match('/<\/?[a-z][a-z0-9]*[\s\/>]/i', $content);
    }

    /**
     * Neutralise remote image sources so opening an entry does not confirm the
     * read to the sender through a tracking pixel.
     *
     * @param string $html
     * @return string
     */
    private function blockRemoteImages(string $html): string
    {
        return (string)preg_replace(
            '/<img\b([^>]*?)\ssrc=(["\'])(https?:\/\/[^"\']*)\2/i',
            '<img$1 data-blocked-src=$2$3$2',
            $html,
        );
    }

    /**
     * @param string $document
     * @return string
     */
    private function wrap(string $document): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            .'<meta name="referrer" content="no-referrer">'
            .'<style>body{margin:0;padding:16px;font-family:system-ui,sans-serif;'
            .'font-size:15px;line-height:1.6;color:#1e293b;}img{max-width:100%;}</style>'
            .'</head><body>'.$document.'</body></html>';
    }
}
