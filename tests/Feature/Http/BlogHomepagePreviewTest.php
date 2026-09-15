<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\PostTranslation;
use Illuminate\Support\Facades\Cache;
use Tests\DatabaseTestCase;

final class BlogHomepagePreviewTest extends DatabaseTestCase
{
    private const LOCALE = 'de';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        // The test client defaults to English, so the requested language is pinned to match the fixtures.
        $this->withHeader('Accept-Language', self::LOCALE);
    }

    /**
     * Publish articles in a known order, newest first.
     *
     * @return list<PostTranslation>
     */
    private function publishArticles(int $count): array
    {
        $articles = [];

        for ($position = 1; $position <= $count; $position++) {
            $articles[] = PostTranslation::factory()->published()->create([
                'locale' => self::LOCALE,
                'title' => 'Startseiten Artikel '.$position,
                'slug' => 'startseiten-artikel-'.$position,
                'published_at' => now()->subDays($position),
            ]);
        }

        return $articles;
    }

    public function testHomepageShowsTheFiveNewestPublishedArticles(): void
    {
        $articles = $this->publishArticles(6);

        $response = $this->get('/');

        $response->assertOk();

        foreach (array_slice($articles, 0, 5) as $article) {
            $response->assertSee($article->title, escape: false);
        }

        $response->assertDontSee($articles[5]->title, escape: false);
    }

    public function testPreviewedArticlesLinkToTheArticleItself(): void
    {
        $articles = $this->publishArticles(1);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('/blog/'.$articles[0]->slug, escape: false);
    }

    public function testHomepageHonoursTheConfiguredArticleLimit(): void
    {
        config(['blog.homepage_limit' => 2]);
        $articles = $this->publishArticles(3);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee($articles[0]->title, escape: false);
        $response->assertSee($articles[1]->title, escape: false);
        $response->assertDontSee($articles[2]->title, escape: false);
    }

    public function testBlogPreviewIsPlacedBeforeTheProcessSection(): void
    {
        $this->publishArticles(5);

        $content = $this->get('/')->assertOk()->getContent();

        $blogPosition = strpos($content, trans('blog.from_the_blog', [], self::LOCALE));
        $processPosition = strpos($content, 'id="ablauf"');

        self::assertIsInt($blogPosition, 'The blog preview is missing from the homepage.');
        self::assertIsInt($processPosition, 'The process section is missing from the homepage.');
        self::assertLessThan($processPosition, $blogPosition);
    }
}
