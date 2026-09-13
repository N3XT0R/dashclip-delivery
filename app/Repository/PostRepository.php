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

class PostRepository
{
    private const EAGER = ['post.author', 'post.category.translations', 'post.tags.translations', 'post.translations'];

    /**
     * Query the translations a reader may see in one language, newest first.
     * @param string $locale
     * @return Builder
     */
    public function publishedForLocale(string $locale): Builder
    {
        return PostTranslation::query()
            ->published()
            ->where('locale', $locale)
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
     * @return Builder
     */
    public function search(string $locale, string $term): Builder
    {
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        return $this->publishedForLocale($locale)
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
     * @return Collection
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

    /** Return the latest public articles, hydrating current author and taxonomy data. */
    public function homepage(string $locale): Collection
    {
        $ids = Cache::remember('blog.homepage.'.$locale, 60, fn () =>
            $this->publishedForLocale($locale)->limit(3)->pluck('id')->all());
        return $this->publishedForLocale($locale)->whereKey($ids)->get();
    }

    /** Return translated categories with public article counts. */
    public function categories(string $locale): Collection
    {
        $counts = Cache::remember('blog.category_counts.'.$locale, 60, fn () => $this->categoryCounts($locale));
        return PostCategoryTranslation::query()->where('locale', $locale)->orderBy('name')->get()
            ->map(fn ($category) => $category->setAttribute('article_count', $counts[$category->category_id] ?? 0));
    }

    /** Return translated tags used by public articles. */
    public function topics(string $locale): Collection
    {
        return PostTagTranslation::query()->where('locale', $locale)
            ->whereHas('tag.posts.translations', fn (Builder $query) => $query->published()->where('locale', $locale))
            ->orderBy('name')->limit(12)->get();
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
