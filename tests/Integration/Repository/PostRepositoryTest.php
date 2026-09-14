<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Exceptions\Blog\PostNotPublishedException;
use App\Application\Blog\ShowPostUseCase;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Repository\PostRepository;
use Tests\DatabaseTestCase;

final class PostRepositoryTest extends DatabaseTestCase
{
    public function testListingsAreScopedToTheActiveLocaleAndNeverLeakOtherLanguages(): void
    {
        $post = Post::factory()->create();
        PostTranslation::factory()->for($post)->published()->create(['locale' => 'de', 'slug' => 'de-a']);
        PostTranslation::factory()->for($post)->published()->create(['locale' => 'en', 'slug' => 'en-a']);

        $repository = app(PostRepository::class);

        self::assertSame(['de-a'], $repository->publishedForLocale('de')->pluck('slug')->all());
        self::assertSame(['en-a'], $repository->publishedForLocale('en')->pluck('slug')->all());
    }

    public function testUnpublishedTranslationsAreNeverReturned(): void
    {
        PostTranslation::factory()->for(Post::factory())->create(['locale' => 'de', 'slug' => 'draft']);

        $repository = app(PostRepository::class);

        self::assertSame([], $repository->publishedForLocale('de')->pluck('slug')->all());
        self::assertNull($repository->findPublishedBySlug('de', 'draft'));
    }

    public function testShowingAnUnpublishedSlugRaisesTheDomainException(): void
    {
        PostTranslation::factory()->for(Post::factory())->create(['locale' => 'de', 'slug' => 'draft']);

        $this->expectException(PostNotPublishedException::class);
        app(ShowPostUseCase::class)->execute('de', 'draft');
    }

    public function testSearchMatchesTitleExcerptAndContentAndRanksTitlesFirst(): void
    {
        PostTranslation::factory()->for(Post::factory())->published()->create([
            'locale' => 'de', 'slug' => 'body', 'title' => 'Ohne Treffer',
            'excerpt' => 'nichts', 'content' => 'hier steht Autobahn im Text',
        ]);
        PostTranslation::factory()->for(Post::factory())->published()->create([
            'locale' => 'de', 'slug' => 'title', 'title' => 'Autobahn und Sicherheit',
            'excerpt' => 'nichts', 'content' => 'nichts',
        ]);

        $slugs = app(PostRepository::class)->search('de', 'Autobahn')->pluck('slug')->all();

        self::assertSame(['title', 'body'], $slugs);
    }
}
