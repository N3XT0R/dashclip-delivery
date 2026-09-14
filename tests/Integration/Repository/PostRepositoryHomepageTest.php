<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Models\PostTranslation;
use App\Repository\PostRepository;
use Illuminate\Support\Facades\Cache;
use Tests\DatabaseTestCase;

final class PostRepositoryHomepageTest extends DatabaseTestCase
{
    private const LOCALE = 'de';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function testChangedLimitIsNotServedFromThePreviousCachedSelection(): void
    {
        for ($position = 1; $position <= 5; $position++) {
            PostTranslation::factory()->published()->create([
                'locale' => self::LOCALE,
                'slug' => 'cached-article-'.$position,
                'published_at' => now()->subDays($position),
            ]);
        }

        $repository = $this->app->make(PostRepository::class);

        config(['blog.homepage_limit' => 3]);
        self::assertCount(3, $repository->homepage(self::LOCALE));

        config(['blog.homepage_limit' => 5]);
        self::assertCount(5, $repository->homepage(self::LOCALE));
    }
}
