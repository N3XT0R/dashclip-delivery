<?php

declare(strict_types=1);

namespace App\Exceptions\Blog;

final class BlogSeedCoverMissingException extends BlogException
{
    /** Name the missing artwork so the shipped file can be restored before seeding again. */
    public static function forArticle(string $article, string $source): self
    {
        return new self('The cover artwork for the blog article "'.$article.'" is missing at '.$source.'. No blog content was created.');
    }
}
