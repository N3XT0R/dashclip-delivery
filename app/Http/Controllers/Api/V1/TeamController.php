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
use Spatie\QueryBuilder\QueryBuilder;

final class TeamController extends ApiController
{
    private const string RESPONSE_MISSING_SCOPE = 'Missing scope';
    private const string SCHEMA_TEAM = '#/components/schemas/Team';

    public function __construct(private readonly TeamRepository $teamRepository)
    {
    }

    private function visibleTeams(Request $request): Builder
    {
        return $this->teamRepository->visibleForUser($this->apiUser($request));
    }

    #[OA\Get(
        path: '/api/v1/teams',
        summary: 'List teams visible to the authenticated user',
        security: [['passport' => ['teams:read']]],
        tags: ['Teams'],
        parameters: [
            new OA\Parameter(
                name: 'filter[name]',
                in: 'query',
                description: 'Partial, case-insensitive match on the team name',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                description: 'Comma-separated sort fields; prefix with "-" for descending. '
                    . 'Allowed: name, created_at. Default: -created_at',
                schema: new OA\Schema(type: 'string'),
            ),
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
                        new OA\Property(
                            property: 'links',
                            properties: [
                                new OA\Property(property: 'first', type: 'string', nullable: true),
                                new OA\Property(property: 'last', type: 'string', nullable: true),
                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                new OA\Property(property: 'next', type: 'string', nullable: true),
                            ],
                            type: 'object',
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $teams = $this->paginate(
            QueryBuilder::for($this->visibleTeams($request))
                ->allowedFilters(AllowedFilter::partial('name'))
                ->allowedSorts('name', 'created_at')
                ->defaultSort('-created_at'),
            $request,
        );

        return $this->paginated(TeamResource::collection($teams));
    }

    #[OA\Get(
        path: '/api/v1/teams/{team}',
        summary: 'Show a single team',
        security: [['passport' => ['teams:read']]],
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE),
            new OA\Response(response: 404, description: 'Team not found or not visible to the user'),
        ],
    )]
    public function show(Request $request, int $team): TeamResource
    {
        return new TeamResource($this->visibleTeams($request)->findOrFail($team));
    }

    #[OA\Post(
        path: '/api/v1/teams',
        summary: 'Create a team owned by the authenticated user',
        security: [['passport' => ['teams:write']]],
        tags: ['Teams'],
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
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
        summary: 'Delete a team owned by the authenticated user',
        security: [['passport' => ['teams:delete']]],
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE),
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
