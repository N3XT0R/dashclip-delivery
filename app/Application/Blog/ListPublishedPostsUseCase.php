<?php

declare(strict_types=1);

namespace App\Application\Blog;

use App\Repository\PostRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListPublishedPostsUseCase
{
    public function __construct(private readonly PostRepository $posts)
    {
    }

    /**
     * @param string $locale
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function execute(string $locale, int $perPage = 9): LengthAwarePaginator
    {
        return $this->posts->publishedForLocale($locale)->paginate($perPage);
    }
}
