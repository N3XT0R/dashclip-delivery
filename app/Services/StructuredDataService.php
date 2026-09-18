<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PostTranslation;
use App\Services\Blog\BlogPresentationService;

/**
 * Build the schema.org descriptions that search engines read from public pages.
 */
final readonly class StructuredDataService
{
    private const NAME = 'DashClip Delivery';

    private const LOGO = 'images/logo.png';

    public function __construct(private BlogPresentationService $blog)
    {
    }

    /**
     * Describe the platform as the organization behind the website.
     * @return array<string, string>
     */
    public function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => self::NAME,
            'url' => url('/'),
            'logo' => asset(self::LOGO),
        ];
    }

    /**
     * Name the website so search results can show the brand instead of the domain.
     * @return array<string, string>
     */
    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::NAME,
            'url' => url('/'),
        ];
    }

    /**
     * Describe a published article including its language, page and publisher.
     * @return array<string, mixed>
     */
    public function article(PostTranslation $article, string $canonical): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->meta_description ?: $article->excerpt,
            'image' => $this->blog->image($article),
            'inLanguage' => $article->locale,
            'mainEntityOfPage' => $canonical,
            'datePublished' => $article->published_at?->toAtomString(),
            'dateModified' => $article->updated_at->toAtomString(),
            'author' => ['@type' => 'Person', 'name' => $article->post->author->display_name],
            'publisher' => [
                '@type' => 'Organization',
                'name' => self::NAME,
                'logo' => ['@type' => 'ImageObject', 'url' => asset(self::LOGO)],
            ],
        ];
    }
}
