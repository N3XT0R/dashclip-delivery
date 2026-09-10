<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreTeamRequest;
use App\Http\Resources\Api\V1\TeamResource;
use App\Repository\TeamRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;

final class TeamController extends ApiController
{
    private const string SCHEMA_TEAM = '#/components/schemas/Team';
    private const string RESPONSE_TEAM_NOT_FOUND = 'Team not found or not visible to the user';

    public function __construct(private readonly TeamRepository $teamRepository)
    {
    }

    private function visibleTeams(Request $request): Builder
    {
        return $this->teamRepository->visibleForUser($this->apiUser($request));
    }

    #[OA\Get(
        path: '/api/v1/teams',
        description: 'Returns a paginated list of teams the user owns or is a member of. Supports '
            . 'partial name filtering, sorting and pagination.',
        summary: 'List teams visible to the authenticated user',
        security: [['passport' => ['teams:read']], ['bearerAuth' => []]],
        tags: ['Teams'],
        parameters: [
            new OA\Parameter(
                name: 'filter[name]',
                description: 'Partial, case-insensitive match on the team name',
                in: 'query',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(ref: '#/components/parameters/SortNameCreatedAt'),
            new OA\Parameter(ref: '#/components/parameters/PageNumber'),
            new OA\Parameter(ref: '#/components/parameters/PageSize'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of teams',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: self::SCHEMA_TEAM),
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/ForbiddenScope', response: 403),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->paginatedList(
            $this->visibleTeams($request),
            TeamResource::class,
            [AllowedFilter::partial('name')],
            ['name', 'created_at'],
            '-created_at',
            $request,
        );
    }

    #[OA\Get(
        path: '/api/v1/teams/{team}',
        description: 'Returns one team the user owns or belongs to, by id. Responds with 404 '
            . 'otherwise.',
        summary: 'Show a single team',
        security: [['passport' => ['teams:read']], ['bearerAuth' => []]],
        tags: ['Teams'],
        parameters: [
            new OA\Parameter(
                name: 'team',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The team',
                content: new OA\JsonContent(ref: self::SCHEMA_TEAM),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/ForbiddenScope', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_TEAM_NOT_FOUND),
        ],
    )]
    public function show(Request $request, int $team): TeamResource
    {
        return new TeamResource($this->visibleTeams($request)->findOrFail($team));
    }

    #[OA\Post(
        path: '/api/v1/teams',
        description: 'Creates a new team with the given name, owned by the authenticated user, '
            . 'who is also added as a member. A unique slug is generated automatically. Returns '
            . '201 with a Location header.',
        summary: 'Create a team owned by the authenticated user',
        security: [['passport' => ['teams:write']], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                ],
                type: 'object',
            ),
        ),
        tags: ['Teams'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Team created',
                headers: [
                    new OA\Header(
                        header: 'Location',
                        description: 'URL of the created team',
                        schema: new OA\Schema(type: 'string'),
                    ),
                ],
                content: new OA\JsonContent(ref: self::SCHEMA_TEAM),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/ForbiddenScope', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = $this->teamRepository->createTeamForOwner(
            $this->apiUser($request),
            $request->validated('name'),
        );

        return (new TeamResource($team))
            ->response($request)
            ->setStatusCode(201)
            ->header('Location', route('api.v1.teams.show', ['team' => $team->getKey()]));
    }

    #[OA\Delete(
        path: '/api/v1/teams/{team}',
        description: 'Deletes a team. Only the team owner may do this; members and non-members '
            . 'get 404. Returns 204 on success.',
        summary: 'Delete a team owned by the authenticated user',
        security: [['passport' => ['teams:delete']], ['bearerAuth' => []]],
        tags: ['Teams'],
        parameters: [
            new OA\Parameter(
                name: 'team',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Team deleted'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/ForbiddenScope', response: 403),
            new OA\Response(response: 404, description: 'Team not found, not visible, or not owned by the user'),
        ],
    )]
    public function destroy(Request $request, int $team): Response
    {
        $model = $this->visibleTeams($request)
            ->where('owner_id', $this->apiUser($request)->getKey())
            ->findOrFail($team);
        $model->delete();

        return $this->noContent();
    }
}
