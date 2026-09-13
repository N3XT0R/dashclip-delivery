<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostRepository
{
    private const EAGER = ['post.author', 'post.category.translations', 'post.tags.translations'];

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
        $pattern = '%'.addcslashes($term, '%_\\').'%';

        return $this->publishedForLocale($locale)
            ->where(static function (Builder $query) use ($pattern): void {
                $query->where('title', 'like', $pattern)
                    ->orWhere('excerpt', 'like', $pattern)
                    ->orWhere('content', 'like', $pattern);
            })
            ->reorder()
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$pattern])
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
}
