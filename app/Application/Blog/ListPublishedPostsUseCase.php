<?php

declare(strict_types=1);

namespace App\Application\Blog;

use App\Repository\PostRepository;
use App\Models\PostTranslation;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListPublishedPostsUseCase
{
    public function __construct(private readonly PostRepository $posts)
    {
    }

    /**
     * Paginate the published translations matching optional taxonomy filters.
     * @param string $locale
     * @param int $perPage
     * @return LengthAwarePaginator<int, PostTranslation>
     */
    public function execute(string $locale, int $perPage = 9, ?int $categoryId = null, ?int $tagId = null): LengthAwarePaginator
    {
        return $this->posts->publishedForLocale($locale, $categoryId, $tagId)->paginate($perPage);
    }
}
