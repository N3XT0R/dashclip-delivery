<?php

declare(strict_types=1);

namespace App\Application\Blog;

use App\Repository\PostRepository;
use App\Models\PostTranslation;
use Illuminate\Pagination\LengthAwarePaginator;

final class SearchPostsUseCase
{
    public function __construct(private readonly PostRepository $posts)
    {
    }

    /**
     * Paginate matching public translations, ranking title matches first.
     * @param string $locale
     * @param string $term
     * @param int $perPage
     * @return LengthAwarePaginator<int, PostTranslation>
     */
    public function execute(string $locale, string $term, int $perPage = 9, ?int $categoryId = null, ?int $tagId = null): LengthAwarePaginator
    {
        return $this->posts->search($locale, $term, $categoryId, $tagId)->paginate($perPage);
    }
}
