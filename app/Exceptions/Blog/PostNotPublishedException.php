<?php

declare(strict_types=1);

namespace App\Exceptions\Blog;

final class PostNotPublishedException extends BlogException
{
    /**
     * @param string $slug
     * @param string $locale
     * @return self
     */
    public static function forSlug(string $slug, string $locale): self
    {
        return new self(sprintf('No published article "%s" exists for locale "%s".', $slug, $locale));
    }
}
