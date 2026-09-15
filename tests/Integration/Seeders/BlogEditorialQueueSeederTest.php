<?php

declare(strict_types=1);

namespace Tests\Integration\Seeders;

use App\Console\Commands\PublishScheduledPostsCommand;
use App\Enum\Blog\PostStatusEnum;
use App\Exceptions\Blog\BlogSeedAuthorMissingException;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\PostTranslation;
use App\Models\User;
use Database\Seeders\BlogEditorialQueueSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class BlogEditorialQueueSeederTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function testSeederSchedulesEveryArticleOneWeekApart(): void
    {
        Carbon::setTestNow('2026-09-15 13:45:00');
        $author = User::factory()->admin()->create();

        $this->seed(BlogEditorialQueueSeeder::class);

        $this->assertSame(67, Post::query()->count());
        $this->assertSame(134, PostTranslation::query()->count());
        $posts = Post::query()->with(['translations', 'tags', 'category'])->orderBy('id')->get();
        foreach ($posts as $index => $post) {
            $this->assertSame($author->id, $post->author_id);
            $this->assertNotEmpty($post->tags, 'Every article carries at least one topic.');
            $this->assertCount(2, $post->translations);
            $expected = Carbon::parse('2026-09-15 13:45:00')->addWeeks($index + 1)->setTime(9, 0);
            foreach ($post->translations as $translation) {
                $this->assertSame(PostStatusEnum::SCHEDULED, $translation->status);
                $this->assertTrue($translation->published_at->equalTo($expected), 'Article '.($index + 1).' goes live in week '.($index + 1).'.');
                $this->assertTrue($translation->is_indexable);
                $this->assertNotEmpty($translation->meta_title);
                $this->assertNotEmpty($translation->meta_description);
                $this->assertGreaterThan(400, count(preg_split('/\s+/u', trim($translation->content))));
                $this->assertGreaterThanOrEqual(3, $translation->reading_minutes);
            }
            $this->assertSame(['de', 'en'], $post->translations->pluck('locale')->sort()->values()->all());
        }
    }

    public function testEveryArticleReferencesACoverThatLandsOnThePublicDisk(): void
    {
        User::factory()->admin()->create();

        $this->seed(BlogEditorialQueueSeeder::class);

        foreach (Post::query()->orderBy('id')->get() as $post) {
            $this->assertNotNull($post->image_path);
            $this->assertStringStartsWith('blog/', $post->image_path);
            $this->assertTrue(Storage::disk('public')->exists($post->image_path), $post->image_path.' is readable for the website.');
            $this->assertGreaterThan(1024, strlen((string)Storage::disk('public')->get($post->image_path)));
        }
    }

    public function testReplacedCoversAreNeverOverwritten(): void
    {
        User::factory()->admin()->create();
        $content = require database_path('seeders/data/blog-editorial-queue.php');
        $replaced = 'blog/'.array_key_first($content['articles']).'.webp';
        Storage::disk('public')->put($replaced, 'an editorial replacement');

        $this->seed(BlogEditorialQueueSeeder::class);

        $this->assertSame('an editorial replacement', Storage::disk('public')->get($replaced));
    }

    public function testTaxonomiesAreSharedWithTheExistingEditorialContent(): void
    {
        User::factory()->admin()->create();
        $category = PostCategory::factory()->create(['slug' => 'dashcam-knowledge', 'icon' => 'video']);
        $category->translations()->create(['locale' => 'de', 'slug' => 'kamera-wissen', 'name' => 'Kamera-Wissen']);
        $tag = PostTag::factory()->create(['slug' => 'dashcams']);
        $tag->translations()->create(['locale' => 'de', 'slug' => 'meine-dashcams', 'name' => 'Meine Dashcams']);

        $this->seed(BlogEditorialQueueSeeder::class);

        $this->assertSame(3, PostCategory::query()->count());
        $this->assertSame('video', $category->fresh()->icon);
        $this->assertSame('Kamera-Wissen', $category->translation('de')->name);
        $this->assertSame('Meine Dashcams', $tag->translation('de')->name);
        $this->assertSame($category->id, Post::query()->where('category_id', $category->id)->first()?->category_id);
    }

    public function testRerunsPreserveRenamedEditedAndDeletedContent(): void
    {
        User::factory()->admin()->create();
        $this->seed(BlogEditorialQueueSeeder::class);
        $initial = $this->snapshot();
        $this->seed(BlogEditorialQueueSeeder::class);
        $this->assertSame($initial, $this->snapshot());

        $post = Post::query()->orderBy('id')->firstOrFail();
        $post->translation('de')->update(['slug' => 'redaktionell-geaendert', 'title' => 'Eigener Titel', 'status' => PostStatusEnum::RETRACTED]);
        $post->translation('en')->delete();
        Post::query()->orderByDesc('id')->firstOrFail()->delete();
        $edited = $this->snapshot();

        $this->seed(BlogEditorialQueueSeeder::class);

        $this->assertSame($edited, $this->snapshot());
        $this->assertSame(66, Post::query()->count());
    }

    public function testMissingAuthorLeavesNoContentOrMarkerAndCanBeRetried(): void
    {
        $user = User::factory()->create();

        try {
            $this->seed(BlogEditorialQueueSeeder::class);
            $this->fail('Expected the seeder to require an existing administrator.');
        } catch (BlogSeedAuthorMissingException) {
            $this->assertSame(0, DB::table('blog_seed_runs')->count());
            $this->assertSame(0, PostCategory::query()->count());
            $this->assertSame(0, Post::query()->count());
        }

        $user->assignRole('super_admin');
        $this->seed(BlogEditorialQueueSeeder::class);
        $this->assertSame(67, Post::query()->count());
    }

    public function testLateFailureRollsBackAllNewRecordsAndAllowsRetry(): void
    {
        User::factory()->admin()->create();
        $content = require database_path('seeders/data/blog-editorial-queue.php');
        $taken = end($content['articles'])['translations']['en']['slug'];
        $conflict = PostTranslation::factory()->create(['locale' => 'en', 'slug' => $taken]);
        $before = $this->snapshot();

        try {
            $this->seed(BlogEditorialQueueSeeder::class);
            $this->fail('Expected the existing article slug to be preserved.');
        } catch (QueryException) {
            $this->assertSame($before, $this->snapshot());
        }

        $conflict->update(['slug' => 'existing-article']);
        $this->seed(BlogEditorialQueueSeeder::class);
        $this->assertSame(68, Post::query()->count());
        $this->assertSame(1, DB::table('blog_seed_runs')->count());
    }

    public function testTheFirstArticleGoesLiveOnceItsPublicationDateArrives(): void
    {
        Carbon::setTestNow('2026-09-15 13:45:00');
        User::factory()->admin()->create();
        $this->seed(BlogEditorialQueueSeeder::class);
        $this->assertSame(0, PostTranslation::query()->published()->count());

        Carbon::setTestNow('2026-09-22 09:30:00');
        Artisan::call(PublishScheduledPostsCommand::class);

        $this->assertSame(2, PostTranslation::query()->published()->count());
        $this->assertSame(132, PostTranslation::query()->where('status', PostStatusEnum::SCHEDULED)->count());
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
