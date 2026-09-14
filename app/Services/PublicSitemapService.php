<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\PostRepository;
use App\Services\Blog\BlogPresentationService;
use Generator;

class PublicSitemapService
{
    public function __construct(private readonly PostRepository $posts, private readonly BlogPresentationService $blog)
    {
    }

    /**
     * Combine the existing indexable public pages with current blog content.
     * @return Generator<int, array{loc: string, lastmod?: string}>
     */
    public function entries(): Generator
    {
        foreach (['home', 'impressum', 'datenschutz', 'tos', 'game', 'api-docs', 'changelog', 'license'] as $route) {
            yield ['loc' => route($route)];
        }
        yield from $this->blogEntries();
    }

    /**
     * Reflect current publication status on every request and omit non-canonical article URLs.
     * Modification dates use persisted article/image changes, never the sitemap request time.
     * @return Generator<int, array{loc: string, lastmod?: string}>
     */
    public function blogEntries(): Generator
    {
        foreach (['de', 'en'] as $locale) {
            yield ['loc' => $this->blog->url('index', $locale)];
            foreach ($this->posts->sitemapCategories($locale) as $category) {
                yield ['loc' => $this->blog->url('category', $locale, ['slug' => $category->slug])];
            }
            foreach ($this->posts->sitemapTags($locale) as $tag) {
                yield ['loc' => $this->blog->url('tag', $locale, ['slug' => $tag->slug])];
            }
        }
        foreach ($this->posts->sitemapArticles()->lazyById(500) as $article) {
            $url = $this->blog->url('show', $article->locale, ['slug' => $article->slug]);
            if (filled($article->canonical_url) && rtrim($article->canonical_url, '/') !== rtrim($url, '/')) {
                continue;
            }
            $updated = $article->updated_at->max($article->post->updated_at);
            yield ['loc' => $url, 'lastmod' => $updated->toAtomString()];
        }
    }
}
