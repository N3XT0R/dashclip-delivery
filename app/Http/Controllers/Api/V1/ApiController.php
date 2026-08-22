<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enum\Guard\GuardEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

abstract class ApiController extends Controller
{
    protected function apiUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user('api');

        return $user;
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
}
