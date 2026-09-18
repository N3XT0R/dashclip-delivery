<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\Post;
use App\Models\PostCategoryTranslation;
use App\Models\PostTranslation;
use DOMDocument;
use DOMXPath;
use Tests\DatabaseTestCase;

/**
 * Verifies the discovery metadata search engines and social networks read from public pages.
 */
final class SeoMetadataTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withHeader('Accept-Language', 'de');
    }

    public function testPaginatedOverviewPagesAreTheirOwnCanonical(): void
    {
        $this->assertSame(url('/blog'), $this->xpath('/blog')->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $this->assertSame(url('/blog?page=2'), $this->xpath('/blog?page=2')->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $this->assertSame(url('/'), $this->xpath('/?page=abc&campaign=test')->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
    }

    public function testOverviewAndCategoryPagesLinkTheirLanguageVersions(): void
    {
        $post = Post::factory()->create();
        PostCategoryTranslation::factory()->create(['category_id' => $post->category_id, 'locale' => 'de', 'slug' => 'neuigkeiten', 'name' => 'Neuigkeiten']);
        PostCategoryTranslation::factory()->create(['category_id' => $post->category_id, 'locale' => 'en', 'slug' => 'news', 'name' => 'News']);

        $this->assertSame(
            ['de' => url('/blog'), 'en' => url('/en/blog'), 'x-default' => url('/blog')],
            $this->alternates('/blog'),
        );
        $this->assertSame(
            ['de' => url('/blog/kategorie/neuigkeiten'), 'en' => url('/en/blog/category/news'), 'x-default' => url('/blog/kategorie/neuigkeiten')],
            $this->alternates('/en/blog/category/news'),
        );
        $this->assertSame([], $this->alternates('/blog/search?q=dashcam'));
        $this->assertSame([], $this->alternates('/blog?page=2'));
    }

    public function testArticleStructuredDataNamesPublisherLanguageAndPage(): void
    {
        $post = Post::factory()->create();
        PostTranslation::factory()->for($post)->published()->create([
            'locale' => 'en', 'slug' => 'structured', 'title' => 'Structured article', 'meta_description' => 'What the article covers.',
        ]);

        $article = $this->structuredData('/en/blog/structured')['Article'];

        $this->assertSame('What the article covers.', $article['description']);
        $this->assertSame('en', $article['inLanguage']);
        $this->assertSame(url('/en/blog/structured'), $article['mainEntityOfPage']);
        $this->assertSame('Organization', $article['publisher']['@type']);
        $this->assertSame('DashClip Delivery', $article['publisher']['name']);
        $this->assertSame(asset('images/logo.png'), $article['publisher']['logo']['url']);
    }

    public function testHomepageDescribesTheOrganizationAndWebsite(): void
    {
        $data = $this->structuredData('/');

        $this->assertSame(url('/'), $data['Organization']['url']);
        $this->assertSame(asset('images/logo.png'), $data['Organization']['logo']);
        $this->assertSame('DashClip Delivery', $data['WebSite']['name']);
        $this->assertSame(url('/'), $data['WebSite']['url']);
    }

    public function testSocialMetadataNamesSiteAndLanguage(): void
    {
        foreach (['/' => 'de_DE', '/en/blog' => 'en_US'] as $path => $locale) {
            $xpath = $this->xpath($path);
            $this->assertSame('DashClip Delivery', $xpath->query('//meta[@property="og:site_name"]')->item(0)?->getAttribute('content'));
            $this->assertSame($locale, $xpath->query('//meta[@property="og:locale"]')->item(0)?->getAttribute('content'));
        }
    }

    private function xpath(string $path): DOMXPath
    {
        $document = new DOMDocument();
        @$document->loadHTML($this->get($path)->assertOk()->getContent());

        return new DOMXPath($document);
    }

    /** @return array<string, string> */
    private function alternates(string $path): array
    {
        $links = [];
        foreach ($this->xpath($path)->query('//head/link[@rel="alternate" and @hreflang]') as $link) {
            $links[$link->getAttribute('hreflang')] = $link->getAttribute('href');
        }

        return $links;
    }

    /** @return array<string, array<string, mixed>> */
    private function structuredData(string $path): array
    {
        $data = [];
        foreach ($this->xpath($path)->query('//script[@type="application/ld+json"]') as $script) {
            $entry = json_decode($script->textContent, true, flags: JSON_THROW_ON_ERROR);
            $data[$entry['@type']] = $entry;
        }

        return $data;
    }
}
