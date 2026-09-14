<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Blog;

use App\Enum\Blog\PostStatusEnum;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\PostCategoryTranslation;
use App\Models\PostTagTranslation;
use Illuminate\Database\QueryException;
use Tests\DatabaseTestCase;

final class PostTest extends DatabaseTestCase
{
    public function testTaxonomyTranslationsResolveTheirOwningCategoryAndTag(): void
    {
        $categoryTranslation = PostCategoryTranslation::factory()->create();
        $tagTranslation = PostTagTranslation::factory()->create();

        $this->assertSame($categoryTranslation->category_id, $categoryTranslation->category->id);
        $this->assertTrue($categoryTranslation->category->translations->contains($categoryTranslation));
        $this->assertSame($tagTranslation->tag_id, $tagTranslation->tag->id);
        $this->assertTrue($tagTranslation->tag->translations->contains($tagTranslation));
    }

    public function testAPostCarriesOneTranslationPerLocale(): void
    {
        $post = Post::factory()->create();
        PostTranslation::factory()->for($post)->create(['locale' => 'de', 'slug' => 'erster-beitrag']);
        PostTranslation::factory()->for($post)->create(['locale' => 'en', 'slug' => 'first-post']);

        self::assertCount(2, $post->refresh()->translations);
        self::assertSame('erster-beitrag', $post->translation('de')->slug);
        self::assertSame('first-post', $post->translation('en')->slug);
        self::assertNull($post->translation('fr'));
    }

    public function testTheSameSlugIsAllowedOncePerLocaleAndRejectedTwiceWithinOne(): void
    {
        PostTranslation::factory()->for(Post::factory())->create(['locale' => 'de', 'slug' => 'shared']);
        PostTranslation::factory()->for(Post::factory())->create(['locale' => 'en', 'slug' => 'shared']);

        $this->expectException(QueryException::class);
        PostTranslation::factory()->for(Post::factory())->create(['locale' => 'de', 'slug' => 'shared']);
    }

    public function testOnlyPublishedTranslationsWithAPastDateAreScopedPublished(): void
    {
        $post = Post::factory()->create();
        PostTranslation::factory()->for($post)->create([
            'locale' => 'de', 'slug' => 'live',
            'status' => PostStatusEnum::PUBLISHED, 'published_at' => now()->subDay(),
        ]);
        PostTranslation::factory()->for($post)->create([
            'locale' => 'en', 'slug' => 'scheduled',
            'status' => PostStatusEnum::SCHEDULED, 'published_at' => now()->addDay(),
        ]);
        $other = Post::factory()->create();
        PostTranslation::factory()->for($other)->create([
            'locale' => 'de', 'slug' => 'future',
            'status' => PostStatusEnum::PUBLISHED, 'published_at' => now()->addDay(),
        ]);
        $retracted = Post::factory()->create();
        PostTranslation::factory()->for($retracted)->create([
            'locale' => 'de', 'slug' => 'gone',
            'status' => PostStatusEnum::RETRACTED, 'published_at' => now()->subDay(),
        ]);

        self::assertSame(['live'], PostTranslation::query()->published()->pluck('slug')->all());
    }

    public function testReadingMinutesAreDerivedOnSaveAndNeverFallBelowOne(): void
    {
        $post = Post::factory()->create();
        $long = PostTranslation::factory()->for($post)->create([
            'locale' => 'de', 'content' => str_repeat('wort ', 400),
        ]);
        $short = PostTranslation::factory()->for(Post::factory())->create([
            'locale' => 'de', 'content' => 'kurz',
        ]);

        self::assertSame(2, $long->reading_minutes);
        self::assertSame(1, $short->reading_minutes);
    }

    public function testUmlautsDoNotInflateTheReadingTime(): void
    {
        $translation = PostTranslation::factory()->for(Post::factory())->create([
            'locale' => 'de', 'content' => str_repeat('Auf der Strasse faehrt ein Lkw ', 100),
        ]);
        $withUmlauts = PostTranslation::factory()->for(Post::factory())->create([
            'locale' => 'de', 'content' => str_repeat('Auf der Straße fährt ein Lkw ', 100),
        ]);

        self::assertSame($translation->reading_minutes, $withUmlauts->reading_minutes);
    }
}
