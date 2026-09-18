<?php

declare(strict_types=1);

namespace Tests\Integration\Seeders;

use App\Enum\Blog\PostStatusEnum;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use Database\Seeders\ReleaseNewsSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class ReleaseNewsSeederTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function testReleaseNewsIsPublishedImmediatelyInGermanAndEnglish(): void
    {
        Carbon::setTestNow('2026-09-18 16:30:00');
        $author = User::factory()->admin()->create();

        $this->app->make(ReleaseNewsSeeder::class)->run('4.9.0');

        $post = Post::query()->with(['translations', 'tags', 'category'])->sole();
        $this->assertSame($author->id, $post->author_id);
        $this->assertSame('news', $post->category->slug);
        $this->assertEqualsCanonicalizing(['dashclip-delivery', 'updates'], $post->tags->pluck('slug')->all());
        $this->assertSame('blog/release-4-9-0.webp', $post->image_path);
        $this->assertTrue(Storage::disk('public')->exists($post->image_path));
        $this->assertSame(['de', 'en'], $post->translations->pluck('locale')->sort()->values()->all());
        foreach ($post->translations as $translation) {
            $this->assertSame(PostStatusEnum::PUBLISHED, $translation->status);
            $this->assertTrue($translation->published_at->equalTo(Carbon::parse('2026-09-18 16:30:00')));
            $this->assertTrue($translation->is_indexable);
            $this->assertStringContainsString('4.9.0', $translation->title);
            $this->assertSame(
                file_get_contents(database_path('seeders/data/releases/4.9.0.'.$translation->locale.'.md')),
                $translation->content,
            );
        }
    }

    public function testDeploymentMigrationPublishesTheArticlePages(): void
    {
        User::factory()->admin()->create();

        (require database_path('migrations/2026_09_18_200000_publish_release_news_4_9_0.php'))->up();

        $this->get('/blog/neu-in-version-4-9-0')
            ->assertOk()
            ->assertSee('Neu in Version 4.9.0: Passkeys und ein aufgeräumtes Profil')
            ->assertSee('Anmelden mit Passkey')
            ->assertSee('href="https://www.bsi.bund.de/DE/', false);
        $this->get('/en/blog/new-in-version-4-9-0')
            ->assertOk()
            ->assertSee('New in version 4.9.0: passkeys and a tidier profile')
            ->assertSee('Sign in with a passkey')
            ->assertSee('href="https://www.bsi.bund.de/DE/', false);
    }

    public function testRefreshUpdatesUneditedPublishedReleaseNews(): void
    {
        User::factory()->admin()->create();
        $this->app->make(ReleaseNewsSeeder::class)->run('4.9.0');
        PostTranslation::query()->update(['content' => 'Previously shipped text.', 'updated_at' => DB::raw('created_at')]);

        (require database_path('migrations/2026_09_18_210000_refresh_release_news_4_9_0.php'))->up();

        foreach (PostTranslation::query()->get() as $translation) {
            $this->assertStringContainsString('bsi.bund.de', $translation->content);
        }
    }

    public function testRefreshKeepsEditorialChanges(): void
    {
        User::factory()->admin()->create();
        $this->app->make(ReleaseNewsSeeder::class)->run('4.9.0');
        PostTranslation::query()->update(['content' => 'Edited by the editorial team.', 'updated_at' => now()->addHour()]);

        $this->app->make(ReleaseNewsSeeder::class)->refreshContent('4.9.0');

        $this->assertSame(['Edited by the editorial team.'], PostTranslation::query()->pluck('content')->unique()->values()->all());
    }

    public function testReleaseNewsIsPublishedOnlyOnce(): void
    {
        User::factory()->admin()->create();

        $this->app->make(ReleaseNewsSeeder::class)->run('4.9.0');
        $this->app->make(ReleaseNewsSeeder::class)->run('4.9.0');

        $this->assertSame(1, Post::query()->count());
        $this->assertSame(2, PostTranslation::query()->count());
    }

    public function testInstallationsWithoutAdministratorAreSkippedUntilOneExists(): void
    {
        $this->app->make(ReleaseNewsSeeder::class)->run('4.9.0');

        $this->assertSame(0, Post::query()->count());
        $this->assertFalse(DB::table('blog_seed_runs')->where('name', 'release-news-4.9.0')->exists());

        User::factory()->admin()->create();
        $this->app->make(ReleaseNewsSeeder::class)->run('4.9.0');

        $this->assertSame(1, Post::query()->count());
    }

    public function testEveryShippedReleaseIsComplete(): void
    {
        $releases = glob(database_path('seeders/data/releases/*.php'));
        $this->assertNotEmpty($releases);

        foreach ($releases as $file) {
            $version = basename($file, '.php');
            $release = require $file;
            $this->assertMatchesRegularExpression('/^release-[0-9a-z-]+$/', $release['key']);
            $this->assertFileExists(database_path('seeders/data/images/covers/'.$release['key'].'.webp'));
            $this->assertSame(['de', 'en'], array_keys($release['translations']));
            foreach ($release['translations'] as $locale => $translation) {
                foreach (['slug', 'title', 'excerpt', 'meta_title', 'meta_description'] as $field) {
                    $this->assertNotEmpty($translation[$field] ?? null, $version.' '.$locale.' needs '.$field.'.');
                }
                $this->assertLessThanOrEqual(255, mb_strlen($translation['meta_title']));
                $this->assertLessThanOrEqual(255, mb_strlen($translation['meta_description']));
                $this->assertFileExists(database_path('seeders/data/releases/'.$version.'.'.$locale.'.md'));
            }
        }
    }
}
