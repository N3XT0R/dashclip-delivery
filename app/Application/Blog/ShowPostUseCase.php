<?php

declare(strict_types=1);

namespace App\Application\Blog;

use App\Exceptions\Blog\PostNotPublishedException;
use App\Models\PostTranslation;
use App\Repository\PostRepository;

final class ShowPostUseCase
{
    public function __construct(private readonly PostRepository $posts)
    {
    }

    /**
     * @param string $locale
     * @param string $slug
     * @return PostTranslation
     * @throws PostNotPublishedException
     */
    public function execute(string $locale, string $slug): PostTranslation
    {
        return $this->posts->findPublishedBySlug($locale, $slug)
            ?? throw PostNotPublishedException::forSlug($slug, $locale);
    }
}
