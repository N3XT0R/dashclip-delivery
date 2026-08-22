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
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class TeamController extends ApiController
{
    public function __construct(private readonly TeamRepository $teamRepository)
    {
    }

    private function visibleTeams(Request $request): Builder
    {
        return $this->teamRepository->visibleForUser($this->apiUser($request));
    }

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

    public function show(Request $request, int $team): TeamResource
    {
        return new TeamResource($this->visibleTeams($request)->findOrFail($team));
    }

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

    public function destroy(Request $request, int $team): Response
    {
        $model = $this->visibleTeams($request)
            ->where('owner_id', $this->apiUser($request)->getKey())
            ->findOrFail($team);
        $model->delete();

        return $this->noContent();
    }
}
