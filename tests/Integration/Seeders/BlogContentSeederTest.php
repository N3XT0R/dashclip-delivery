<?php

declare(strict_types=1);

namespace Tests\Integration\Seeders;

use App\Enum\Blog\PostStatusEnum;
use App\Exceptions\Blog\BlogSeedAuthorMissingException;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\PostTranslation;
use App\Models\User;
use Database\Seeders\BlogContentSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\DatabaseTestCase;

class BlogContentSeederTest extends DatabaseTestCase
{
    public function testSeederPublishesTheRealIntroductionWithTranslatedTaxonomies(): void
    {
        $author = User::factory()->admin()->create();
        $this->seed(BlogContentSeeder::class);
        $this->assertSame(3, PostCategory::query()->count());
        $this->assertSame(5, PostTag::query()->count());
        $this->assertSame(1, Post::query()->count());
        $this->assertSame(6, DB::table('blog_category_translations')->count());
        $this->assertSame(10, DB::table('blog_tag_translations')->count());
        $post = Post::query()->sole();
        $this->assertSame($author->id, $post->author_id);
        $this->assertSame('news', $post->category->slug);
        $this->assertCount(3, $post->tags);
        $this->assertCount(2, $post->translations);
        foreach ($post->translations as $translation) {
            $this->assertSame(PostStatusEnum::PUBLISHED, $translation->status);
            $this->assertTrue($translation->published_at->lessThanOrEqualTo(now()));
            $this->assertTrue($translation->is_indexable);
            $this->assertGreaterThan(400, count(preg_split('/\s+/u', trim($translation->content))));
            $this->assertGreaterThanOrEqual(2, $translation->reading_minutes);
        }
    }

    public function testRerunsPreserveRenamedEditedAndDeletedContent(): void
    {
        User::factory()->admin()->create();
        $this->seed(BlogContentSeeder::class);
        $initial = $this->snapshot();
        $this->seed(BlogContentSeeder::class);
        $this->assertSame($initial, $this->snapshot());

        $post = Post::query()->sole();
        $post->translation('de')->update(['slug' => 'redaktionell-geaendert', 'title' => 'Eigener Titel', 'content' => 'Eigener Text', 'status' => PostStatusEnum::RETRACTED]);
        $post->translation('en')->delete();
        $post->category->update(['slug' => 'renamed-news']);
        $post->category->translation('de')->update(['name' => 'Redaktion', 'slug' => 'redaktion']);
        $post->tags->first()->update(['slug' => 'renamed-tag']);
        $post->tags()->detach();
        PostTag::query()->where('slug', 'dashcams')->firstOrFail()->delete();
        PostCategory::query()->where('slug', 'dashcam-knowledge')->firstOrFail()->delete();
        $edited = $this->snapshot();
        $this->seed(BlogContentSeeder::class);
        $this->assertSame($edited, $this->snapshot());

        $post->delete();
        $this->seed(BlogContentSeeder::class);
        $this->assertSame(0, Post::query()->count());
        $this->assertSame(0, PostTranslation::query()->count());
    }

    public function testExistingTaxonomiesAreReusedWithoutOverwritingTheirLabels(): void
    {
        User::factory()->admin()->create();
        $category = PostCategory::factory()->create(['slug' => 'news', 'icon' => 'camera']);
        $category->translations()->create(['locale' => 'de', 'slug' => 'unsere-news', 'name' => 'Unsere News']);
        $tag = PostTag::factory()->create(['slug' => 'updates']);
        $tag->translations()->create(['locale' => 'de', 'slug' => 'unsere-updates', 'name' => 'Unsere Updates']);
        $this->seed(BlogContentSeeder::class);
        $this->assertSame(3, PostCategory::query()->count());
        $this->assertSame(5, PostTag::query()->count());
        $this->assertSame($category->id, Post::query()->sole()->category_id);
        $this->assertSame('camera', $category->fresh()->icon);
        $this->assertSame('Unsere News', $category->translation('de')->name);
        $this->assertSame('Unsere Updates', $tag->translation('de')->name);
    }

    public function testMissingAuthorLeavesNoContentOrMarkerAndCanBeRetried(): void
    {
        $user = User::factory()->create();
        try {
            $this->seed(BlogContentSeeder::class);
            $this->fail('Expected the seeder to require an existing administrator.');
        } catch (BlogSeedAuthorMissingException) {
            $this->assertSame(0, DB::table('blog_seed_runs')->count());
            $this->assertSame(0, PostCategory::query()->count());
            $this->assertSame(0, Post::query()->count());
            $this->assertSame(1, User::query()->count());
        }
        $user->assignRole('super_admin');
        $this->seed(BlogContentSeeder::class);
        $this->assertSame(1, Post::query()->count());
    }

    public function testLateFailureRollsBackAllNewRecordsAndAllowsRetry(): void
    {
        User::factory()->admin()->create();
        $conflict = PostTranslation::factory()->create(['locale' => 'en', 'slug' => 'welcome-to-the-dashclip-blog']);
        $before = $this->snapshot();
        try {
            $this->seed(BlogContentSeeder::class);
            $this->fail('Expected the existing article slug to be preserved.');
        } catch (QueryException) {
            $this->assertSame($before, $this->snapshot());
        }
        $conflict->update(['slug' => 'existing-article']);
        $this->seed(BlogContentSeeder::class);
        $this->assertSame(2, Post::query()->count());
        $this->assertSame(1, DB::table('blog_seed_runs')->count());
    }

    /** @return array<string, string> Exact persisted content, relationships and execution markers. */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['blog_posts', 'blog_post_translations', 'blog_categories', 'blog_category_translations', 'blog_tags', 'blog_tag_translations', 'blog_seed_runs'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy($table === 'blog_seed_runs' ? 'name' : 'id')->get()->toJson();
        }
        $snapshot['blog_post_tag'] = DB::table('blog_post_tag')->orderBy('post_id')->orderBy('tag_id')->get()->toJson();
        return $snapshot;
    }
}
