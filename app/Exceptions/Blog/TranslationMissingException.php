<?php

declare(strict_types=1);

namespace App\Exceptions\Blog;

final class TranslationMissingException extends BlogException
{
    /**
     * @param int $postId
     * @param string $locale
     * @return self
     */
    public static function forLocale(int $postId, string $locale): self
    {
        return new self(sprintf('Post "%d" has no translation for locale "%s".', $postId, $locale));
    }
}
