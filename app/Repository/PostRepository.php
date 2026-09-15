<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\PostCategoryTranslation;
use App\Models\PostTagTranslation;
use App\Models\PostTag;

class PostRepository
{
    private const EAGER = ['post.author', 'post.category.translations', 'post.tags.translations', 'post.translations'];

    /** Used when the blog configuration is unavailable. */
    private const DEFAULT_HOMEPAGE_LIMIT = 5;

    /**
     * Return indexable public translations without loading unrelated presentation data.
     * @return Builder<PostTranslation>
     */
    public function sitemapArticles(): Builder
    {
        return PostTranslation::query()->published()->where('is_indexable', true)
            ->whereIn('locale', ['de', 'en'])->with('post:id,updated_at');
    }

    /**
     * Return categories with published content using fresh visibility data.
     * @return Collection<int, PostCategoryTranslation>
     */
    public function sitemapCategories(string $locale): Collection
    {
        return PostCategoryTranslation::query()->where('locale', $locale)
            ->whereIn('category_id', $this->categoryCounts($locale)->keys())->orderBy('id')->get();
    }

    /**
     * Return all translated tags with published content, without the sidebar cache or limit.
     * @return Collection<int, PostTagTranslation>
     */
    public function sitemapTags(string $locale): Collection
    {
        return PostTagTranslation::query()->where('locale', $locale)
            ->whereHas('tag.posts.translations', fn (Builder $query) => $query->published()->where('locale', $locale))
            ->orderBy('id')->get();
    }

    /**
     * Query the translations a reader may see in one language, newest first.
     * @param string $locale
     * @return Builder<PostTranslation>
     */
    public function publishedForLocale(string $locale, ?int $categoryId = null, ?int $tagId = null): Builder
    {
        return PostTranslation::query()
            ->published()
            ->where('locale', $locale)
            ->when($categoryId !== null, fn (Builder $query) => $query->whereHas('post', fn (Builder $query) => $query->where('category_id', $categoryId)))
            ->when($tagId !== null, fn (Builder $query) => $query->whereHas('post.tags', fn (Builder $query) => $query->whereKey($tagId)))
            ->with(self::EAGER)
            ->orderByDesc('published_at');
    }

    /**
     * Resolve one published article by its locale-scoped slug.
     * @param string $locale
     * @param string $slug
     * @return PostTranslation|null
     */
    public function findPublishedBySlug(string $locale, string $slug): ?PostTranslation
    {
        return $this->publishedForLocale($locale)->where('slug', $slug)->first();
    }

    /**
     * Search title, excerpt and content, ranking title matches first.
     * LIKE is deliberate: the suite runs on SQLite while production runs MariaDB,
     * so a driver-dependent fulltext path would sit outside test coverage.
     * @param string $locale
     * @param string $term
     * @return Builder<PostTranslation>
     */
    public function search(string $locale, string $term, ?int $categoryId = null, ?int $tagId = null): Builder
    {
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        return $this->publishedForLocale($locale, $categoryId, $tagId)
            ->where(static function (Builder $query) use ($pattern): void {
                $query->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("excerpt LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("content LIKE ? ESCAPE '!'", [$pattern]);
            })
            ->reorder()
            ->orderByRaw("CASE WHEN title LIKE ? ESCAPE '!' THEN 0 ELSE 1 END", [$pattern])
            ->orderByDesc('published_at');
    }

    /**
     * Count the published articles per category for the sidebar.
     * @param string $locale
     * @return Collection<int, int>
     */
    public function categoryCounts(string $locale): Collection
    {
        return $this->publishedForLocale($locale)
            ->join('blog_posts', 'blog_posts.id', '=', 'blog_post_translations.post_id')
            ->select('blog_posts.category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('blog_posts.category_id')
            ->reorder()
            ->pluck('total', 'category_id');
    }

    /**
     * Return the latest public articles, hydrating current author and taxonomy data.
     * The amount is taken from the blog configuration and is part of the cache key, so a changed
     * limit does not keep serving the previously cached selection.
     * @return Collection<int, PostTranslation>
     */
    public function homepage(string $locale): Collection
    {
        $limit = self::homepageLimit();
        $ids = Cache::remember(self::homepageCacheKey($locale), 60, fn () =>
            $this->publishedForLocale($locale)->limit($limit)->pluck('id')->all());
        return $this->publishedForLocale($locale)->whereKey($ids)->get();
    }

    /** Amount of articles the start page previews. */
    public static function homepageLimit(): int
    {
        return (int)config('blog.homepage_limit', self::DEFAULT_HOMEPAGE_LIMIT);
    }

    /**
     * Cache key of the start page selection. The limit is part of it, so a changed value is not
     * answered from the previously cached selection. Invalidation must use this same key.
     */
    public static function homepageCacheKey(string $locale): string
    {
        return 'blog.homepage.'.$locale.'.'.self::homepageLimit();
    }

    /**
     * Return translated categories with public article counts.
     * @return Collection<int, PostCategoryTranslation>
     */
    public function categories(string $locale): Collection
    {
        $counts = Cache::remember('blog.category_counts.'.$locale, 60, fn () => $this->categoryCounts($locale));
        return PostCategoryTranslation::query()->where('locale', $locale)->orderBy('name')->get()
            ->map(fn ($category) => $category->setAttribute('article_count', $counts[$category->category_id] ?? 0));
    }

    /**
     * Return translated tags ranked by published article count.
     * @return Collection<int, PostTagTranslation>
     */
    public function topics(string $locale, ?int $limit = 12): Collection
    {
        $topics = Cache::remember('blog.topics.'.$locale, 60, function () use ($locale): Collection {
            $published = fn (Builder $query) => $query->whereHas('translations', fn (Builder $query) => $query->published()->where('locale', $locale));
            return PostTag::query()->whereHas('posts', $published)
                ->whereHas('translations', fn (Builder $query) => $query->where('locale', $locale))
                ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
                ->withCount(['posts' => $published])->orderByDesc('posts_count')->orderBy('id')->get()
                ->map(fn (PostTag $tag) => $tag->translation($locale));
        });
        return $limit === null ? $topics : $topics->take($limit);
    }

    /** Resolve a translated category without exposing internal identifiers. */
    public function category(string $locale, string $slug): PostCategoryTranslation
    {
        return PostCategoryTranslation::query()->where('locale', $locale)->where('slug', $slug)->firstOrFail();
    }

    /** Resolve a translated tag without exposing internal identifiers. */
    public function tag(string $locale, string $slug): PostTagTranslation
    {
        return PostTagTranslation::query()->where('locale', $locale)->where('slug', $slug)->firstOrFail();
    }
}
