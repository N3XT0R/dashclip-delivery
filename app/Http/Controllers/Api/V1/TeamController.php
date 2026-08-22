<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreTeamRequest;
use App\Http\Resources\Api\V1\TeamResource;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class TeamController extends ApiController
{
    private function visibleTeams(Request $request): Builder
    {
        $user = $this->apiUser($request);

        return Team::query()->where(function (Builder $query) use ($user): void {
            $query->where('owner_id', $user->getKey())
                ->orWhereHas('users', static function (Builder $member) use ($user): void {
                    $member->whereKey($user->getKey());
                });
        });
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
        $user = $this->apiUser($request);
        $team = Team::query()->create([
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('name')) . '-' . Str::lower(Str::random(6)),
            'owner_id' => $user->getKey(),
        ]);
        $team->users()->attach($user->getKey());

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
