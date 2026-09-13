<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enum\Blog\PostStatusEnum;
use App\Models\PostTranslation;
use App\Models\PostTag;
use SimpleXMLElement;
use Tests\DatabaseTestCase;

class PublicSitemapTest extends DatabaseTestCase
{
    public function testRootSitemapAndRobotsExposeOnlyThePublicPageCatalog(): void
    {
        $urls = $this->urls($this->sitemap());
        foreach (['home', 'impressum', 'datenschutz', 'tos', 'game', 'api-docs', 'changelog', 'license', 'blog.index', 'blog.en.index'] as $route) {
            $this->assertContains(route($route), $urls);
        }
        $this->assertCount(10, $urls);
        $this->assertNotContains(route('blog.search'), $urls);
        $this->assertNotContains(route('filament.standard.auth.register'), $urls);
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('sitemap'), false);
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function testBothLanguagesAreIncludedButPrivateAndNonCanonicalArticlesAreExcluded(): void
    {
        $german = PostTranslation::factory()->published()->create(['locale' => 'de', 'slug' => 'deutsch']);
        $english = PostTranslation::factory()->published()->create(['post_id' => $german->post_id, 'locale' => 'en', 'slug' => 'english']);
        foreach ([PostStatusEnum::DRAFT, PostStatusEnum::SCHEDULED, PostStatusEnum::RETRACTED] as $status) {
            PostTranslation::factory()->create(['slug' => $status->value, 'status' => $status, 'published_at' => now()->subDay()]);
        }
        PostTranslation::factory()->published()->create(['slug' => 'future', 'published_at' => now()->addDay()]);
        PostTranslation::factory()->published()->create(['slug' => 'noindex', 'is_indexable' => false]);
        PostTranslation::factory()->published()->create(['slug' => 'external-canonical', 'canonical_url' => 'https://elsewhere.example/article']);
        $german->update(['canonical_url' => route('blog.show', $german->slug)]);
        foreach (['/sitemap.xml', '/blog-sitemap.xml'] as $path) {
            $urls = $this->urls($this->sitemap($path));
            $this->assertContains(route('blog.show', $german->slug), $urls);
            $this->assertContains(route('blog.en.show', $english->slug), $urls);
            foreach (['draft', 'scheduled', 'retracted', 'future', 'noindex', 'external-canonical'] as $slug) {
                $this->assertNotContains(route('blog.show', $slug), $urls);
            }
            $this->assertNotContains('https://elsewhere.example/article', $urls);
        }
    }

    public function testPublicationRenameAndRetractionImmediatelyUpdateArticleAndTaxonomyUrls(): void
    {
        $this->withoutVite();
        $article = PostTranslation::factory()->create(['slug' => 'first-slug', 'locale' => 'de']);
        $article->post->category->translations()->create(['locale' => 'de', 'slug' => 'news', 'name' => 'Neuigkeiten']);
        $tag = PostTag::factory()->create();
        $tag->translations()->create(['locale' => 'de', 'slug' => 'updates', 'name' => 'Updates']);
        $article->post->tags()->attach($tag);
        $this->get('/blog')->assertOk();
        $urls = $this->urls($this->sitemap());
        $this->assertNotContains(route('blog.show', 'first-slug'), $urls);
        $this->assertNotContains(route('blog.category', 'news'), $urls);
        $this->assertNotContains(route('blog.tag', 'updates'), $urls);
        $article->update(['status' => PostStatusEnum::PUBLISHED, 'published_at' => now()->subMinute()]);
        $urls = $this->urls($this->sitemap());
        $this->assertContains(route('blog.show', 'first-slug'), $urls);
        $this->assertContains(route('blog.category', 'news'), $urls);
        $this->assertContains(route('blog.tag', 'updates'), $urls);
        $article->update(['slug' => 'renamed']);
        $urls = $this->urls($this->sitemap());
        $this->assertNotContains(route('blog.show', 'first-slug'), $urls);
        $this->assertContains(route('blog.show', 'renamed'), $urls);
        $article->update(['status' => PostStatusEnum::RETRACTED]);
        $urls = $this->urls($this->sitemap());
        $this->assertNotContains(route('blog.show', 'renamed'), $urls);
        $this->assertNotContains(route('blog.category', 'news'), $urls);
        $this->assertNotContains(route('blog.tag', 'updates'), $urls);
    }

    public function testLastModifiedReflectsContentChangesRatherThanRequestTime(): void
    {
        $this->freezeTime();
        $article = PostTranslation::factory()->published()->create(['slug' => 'modified']);
        $first = $this->sitemap()->asXML();
        $this->travel(1)->hours();
        $this->assertSame($first, $this->sitemap()->asXML());
        $article->update(['content' => 'An updated article.']);
        $updated = $this->sitemap()->asXML();
        $this->assertNotSame($first, $updated);
        $this->assertStringContainsString('<lastmod>'.$article->fresh()->updated_at->toAtomString().'</lastmod>', $updated);
    }

    /** Parse the actual endpoint response so invalid XML and namespaces fail the test. */
    private function sitemap(string $path = '/sitemap.xml'): SimpleXMLElement
    {
        $response = $this->get($path)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getDocNamespaces()['']);
        return $xml;
    }

    /** @return list<string> Absolute public URLs from a parsed sitemap. */
    private function urls(SimpleXMLElement $xml): array
    {
        $urls = [];
        foreach ($xml->url as $entry) {
            $urls[] = (string) $entry->loc;
        }
        return $urls;
    }
}
