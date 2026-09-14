<?php

declare(strict_types=1);

namespace App\Services\Blog;

final class ReadingTimeService
{
    private const WORDS_PER_MINUTE = 200;

    /**
     * Derive the displayed reading time, never returning less than one minute.
     * Split on whitespace rather than using str_word_count, which treats German
     * umlauts as word boundaries and overcounts by roughly a third.
     * @param string $content
     * @return int
     */
    public function minutesFor(string $content): int
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        $words = $text === '' ? 0 : count(explode(' ', $text));

        return max(1, (int)ceil($words / self::WORDS_PER_MINUTE));
    }
}
