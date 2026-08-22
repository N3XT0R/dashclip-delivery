<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enum\Guard\GuardEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

abstract class ApiController extends Controller
{
    /**
     * @return User
     */
    protected function apiUser(Request $request): User
    {
        return $request->user('api');
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeApi(Request $request, string $permission): void
    {
        if (!$this->apiUser($request)->checkPermissionTo($permission, GuardEnum::STANDARD->value)) {
            throw new AuthorizationException();
        }
    }

    protected function pageSize(Request $request): int
    {
        $size = (int)$request->input('page.size', config('api.pagination.default_size'));

        return max(1, min($size, (int)config('api.pagination.max_size')));
    }

    protected function pageNumber(Request $request): int
    {
        return max(1, (int)$request->input('page.number', 1));
    }

    protected function paginated(AnonymousResourceCollection $collection): JsonResponse
    {
        $paginator = $collection->resource;

        return response()->json([
            'data' => $collection->resolve(),
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    protected function noContent(): Response
    {
        return response()->noContent();
    }

    /**
     * Paginate an API query so generated pagination links keep the
     * page[number]/page[size] format together with active filters and sorts.
     */
    protected function paginate(QueryBuilder $query, Request $request): LengthAwarePaginator
    {
        return $query
            ->paginate(
                perPage: $this->pageSize($request),
                pageName: 'page[number]',
                page: $this->pageNumber($request),
            )
            ->appends(array_merge(
                Arr::except($request->query(), ['page']),
                ['page' => ['size' => $this->pageSize($request)]],
            ));
    }
}
