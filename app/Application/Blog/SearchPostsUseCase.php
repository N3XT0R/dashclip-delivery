<?php

declare(strict_types=1);

namespace App\Application\Blog;

use App\Repository\PostRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final class SearchPostsUseCase
{
    public function __construct(private readonly PostRepository $posts)
    {
    }

    /**
     * @param string $locale
     * @param string $term
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function execute(string $locale, string $term, int $perPage = 9): LengthAwarePaginator
    {
        return $this->posts->search($locale, $term)->paginate($perPage);
    }
}
