<?php

declare(strict_types=1);

namespace App\Services\Mail;

/**
 * Decode a raw mail payload into a readable body.
 *
 * Entries written before the reading fix hold the message as it arrived, so the
 * transfer encoding and, for multipart messages, the part selection still have
 * to be resolved here.
 */
final class RawMailBodyDecoderService
{
    /**
     * Turn a raw payload into a readable body and the format it is in.
     *
     * @param string $raw
     * @return array{content: string, format: string}|null Null when nothing readable can be recovered.
     */
    public function decode(string $raw): ?array
    {
        if (trim($raw) === '') {
            return null;
        }

        $normalised = str_replace("\r\n", "\n", $raw);

        // A bounce notification opens with a human preamble and never declares
        // the boundary in a header, so recognise it from the delimiter line.
        $boundary = $this->boundaryOf($normalised) ?? $this->delimiterBoundaryOf($normalised);

        if ($boundary !== null) {
            $decoded = $this->decodeMultipart($normalised, $boundary);

            if ($decoded !== null) {
                return $decoded;
            }
        }

        [$headers, $body] = $this->split($raw);
        $decoded = $this->applyTransferEncoding($body, $headers);

        // Many entries hold nothing but the encoded payload, with no headers at
        // all, so there is no transfer encoding to read and it must be detected.
        if ($decoded === $body) {
            $decoded = $this->decodeBareBase64($body) ?? $decoded;
        }

        if (trim($decoded) === '' || $decoded === $raw || $this->looksLikeBase64($decoded)) {
            return null;
        }

        return ['content' => $decoded, 'format' => $this->formatOf($headers, $decoded)];
    }

    /**
     * @param string $raw
     * @return array{0: string, 1: string}
     */
    private function split(string $raw): array
    {
        $normalised = str_replace("\r\n", "\n", $raw);
        $position = strpos($normalised, "\n\n");

        if ($position === false) {
            return ['', $normalised];
        }

        return [substr($normalised, 0, $position), substr($normalised, $position + 2)];
    }

    /**
     * Recover a payload that is nothing but an encoded block.
     *
     * @param string $body
     * @return string|null
     */
    private function decodeBareBase64(string $body): ?string
    {
        if (!$this->looksLikeBase64($body)) {
            return null;
        }

        $decoded = base64_decode(preg_replace('/\s+/', '', $body) ?? '', true);

        if ($decoded === false || trim($decoded) === '' || !mb_check_encoding($decoded, 'UTF-8')) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function looksLikeBase64(string $value): bool
    {
        $compact = preg_replace('/\s+/', '', $value) ?? '';

        return strlen($compact) >= 24
            && strlen($compact) % 4 === 0
            && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $compact) === 1;
    }

    /**
     * Read the boundary from a delimiter line when no header declares one.
     *
     * @param string $raw
     * @return string|null
     */
    private function delimiterBoundaryOf(string $raw): ?string
    {
        if (preg_match('/^--([A-Za-z0-9\'()+_,.\/:=?-]{8,})\s*$/m', $raw, $match) === 1) {
            return rtrim($match[1], '-');
        }

        return null;
    }

    /**
     * @param string $headers
     * @return string|null
     */
    private function boundaryOf(string $headers): ?string
    {
        if (preg_match('/boundary="?([^"\s;]+)"?/i', $headers, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    /**
     * Prefer the HTML part of a multipart message and fall back to its text part.
     *
     * @param string $body
     * @param string $boundary
     * @return array{content: string, format: string}|null
     */
    private function decodeMultipart(string $body, string $boundary): ?array
    {
        $parts = preg_split('/^--'.preg_quote($boundary, '/').'(--)?\s*$/m', $body) ?: [];

        $found = [];
        foreach ($parts as $part) {
            if (trim($part) === '') {
                continue;
            }

            [$partHeaders, $partBody] = $this->split(ltrim($part, "\n"));
            $decoded = $this->applyTransferEncoding($partBody, $partHeaders);

            if (trim($decoded) === '') {
                continue;
            }

            $format = $this->formatOf($partHeaders, $decoded);
            $found[$format] ??= $decoded;
        }

        foreach (['html', 'text'] as $format) {
            if (isset($found[$format])) {
                return ['content' => $found[$format], 'format' => $format];
            }
        }

        return null;
    }

    /**
     * @param string $body
     * @param string $headers
     * @return string
     */
    private function applyTransferEncoding(string $body, string $headers): string
    {
        if (preg_match('/Content-Transfer-Encoding:\s*([^\s;]+)/i', $headers, $match) !== 1) {
            return $body;
        }

        return match (strtolower(trim($match[1]))) {
            'base64' => (string)base64_decode(preg_replace('/\s+/', '', $body) ?? '', true),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
    }

    /**
     * @param string $headers
     * @param string $decoded
     * @return string
     */
    private function formatOf(string $headers, string $decoded): string
    {
        if (preg_match('/Content-Type:\s*text\/html/i', $headers) === 1) {
            return 'html';
        }

        if (preg_match('/Content-Type:\s*text\/plain/i', $headers) === 1) {
            return 'text';
        }

        return preg_match('/<\/?[a-z][a-z0-9]*[\s\/>]/i', $decoded) === 1 ? 'html' : 'text';
    }
}
