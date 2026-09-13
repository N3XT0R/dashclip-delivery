<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enum\Blog\PostStatusEnum;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\PostCategoryTranslation;
use App\Models\User;
use Tests\DatabaseTestCase;

final class BlogPagesTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withHeader('Accept-Language', 'de');
    }

    public function testPublishedArticlesAreVisibleAndUnsafeMarkdownIsNotExecuted(): void
    {
        $author = User::factory()->create(['name' => 'Internal Name', 'submitted_name' => 'Public Author']);
        $post = Post::factory()->create(['author_id' => $author->id]);
        PostTranslation::factory()->for($post)->published()->create([
            'slug' => 'hello', 'title' => 'Public article', 'content' => '## Heading'.PHP_EOL.'<script>alert(1)</script>'.PHP_EOL.'[bad](javascript:alert(1))',
        ]);
        $this->get('/blog')->assertOk()->assertSee('Public article')->assertSee('Public Author');
        $this->get('/blog/hello')->assertOk()->assertSee('Public article')->assertSee('Public Author')
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('href="javascript:', false)
            ->assertSee('name="robots" content="index, follow"', false)->assertSee('"@type":"Article"', false);
        $this->get('/blog/feed.xml')->assertOk()->assertSee('Public article');
        $this->get('/blog-sitemap.xml')->assertOk()->assertSee('/blog/hello');
    }

    public function testDraftFutureAndRetractedArticlesStayPrivate(): void
    {
        foreach ([PostStatusEnum::DRAFT, PostStatusEnum::SCHEDULED, PostStatusEnum::RETRACTED, PostStatusEnum::PUBLISHED] as $status) {
            $slug = 'hidden-'.$status->value;
            PostTranslation::factory()->create(['slug' => $slug, 'title' => $slug, 'status' => $status, 'published_at' => now()->addDay()]);
            $this->get('/blog/'.$slug)->assertNotFound();
            $this->get('/blog')->assertOk()->assertDontSee($slug);
            $this->get('/blog/feed.xml')->assertOk()->assertDontSee($slug);
            $this->get('/blog-sitemap.xml')->assertOk()->assertDontSee($slug);
        }
    }

    public function testUrlLocaleOverridesBrowserAndSwitchesToPublishedSibling(): void
    {
        $post = Post::factory()->create();
        PostTranslation::factory()->for($post)->published()->create(['locale' => 'de', 'slug' => 'deutsch']);
        PostTranslation::factory()->for($post)->published()->create(['locale' => 'en', 'slug' => 'english', 'title' => 'English article']);
        $this->get('/en/blog/english')->assertOk()->assertHeader('Content-Language', 'en')
            ->assertSee('href="'.url('/blog/deutsch').'"', false)->assertSee('hreflang="de"', false);
        $this->withHeader('Accept-Language', 'en')->get('/blog/deutsch')->assertOk()->assertHeader('Content-Language', 'de')
            ->assertSee('href="'.url('/en/blog/english').'"', false);
        $this->get('/blog/english')->assertNotFound();
    }

    public function testMissingTranslationSwitchFallsBackToOverviewAndNoindexIsRespected(): void
    {
        PostTranslation::factory()->published()->create(['slug' => 'only-de', 'is_indexable' => false]);
        $this->get('/blog/only-de')->assertOk()->assertSee('href="'.url('/en/blog').'"', false)
            ->assertSee('noindex, nofollow')->assertDontSee('rel="canonical"', false);
        $this->get('/blog-sitemap.xml')->assertOk()->assertDontSee('/blog/only-de');
    }

    public function testSearchAndTaxonomyPagesFilterPublishedArticles(): void
    {
        $post = Post::factory()->create();
        PostCategoryTranslation::factory()->create(['category_id' => $post->category_id, 'locale' => 'de', 'slug' => 'technik', 'name' => 'Technik']);
        PostTranslation::factory()->for($post)->published()->create(['slug' => 'camera', 'title' => 'Camera choice', 'content' => 'A literal 100% result.']);
        $this->get('/blog/search?q=Camera')->assertOk()->assertSee('Camera choice');
        $this->get('/blog/search?q=100%25')->assertOk()->assertSee('Camera choice');
        $this->get('/blog/search?q=missing-query')->assertOk()->assertSee(__('blog.empty_search'));
        $this->get('/blog/kategorie/technik')->assertOk()->assertSee('Camera choice');
        $this->get('/blog/kategorie/missing')->assertNotFound();
    }

    public function testSchedulerPublishesOnlyDueTranslationsAndInvalidatesHomepage(): void
    {
        $due = PostTranslation::factory()->create(['slug' => 'due', 'title' => 'Due article', 'status' => PostStatusEnum::SCHEDULED, 'published_at' => now()->subMinute()]);
        $future = PostTranslation::factory()->create(['status' => PostStatusEnum::SCHEDULED, 'published_at' => now()->addDay()]);
        $this->get('/')->assertOk()->assertDontSee('Due article');
        $this->artisan('blog:publish-scheduled')->assertSuccessful();
        $this->assertSame(PostStatusEnum::PUBLISHED, $due->fresh()->status);
        $this->assertSame(PostStatusEnum::SCHEDULED, $future->fresh()->status);
        $this->get('/')->assertOk()->assertSee('Due article');
    }
}
