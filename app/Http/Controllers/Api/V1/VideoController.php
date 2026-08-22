<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\VideoResource;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VideoController extends ApiController
{
    private function visibleVideos(Request $request): Builder
    {
        return Video::query()->hasUsersClips($this->apiUser($request));
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeApi($request, 'ViewAny:Video');

        $videos = $this->paginate(
            QueryBuilder::for($this->visibleVideos($request))
                ->allowedFilters(
                    AllowedFilter::partial('original_name'),
                    AllowedFilter::exact('processing_status'),
                    AllowedFilter::callback(
                        'created_after',
                        static fn (Builder $query, mixed $value) => $query->where('created_at', '>=', $value),
                    ),
                    AllowedFilter::callback(
                        'created_before',
                        static fn (Builder $query, mixed $value) => $query->where('created_at', '<=', $value),
                    ),
                )
                ->allowedSorts('original_name', 'created_at', 'bytes')
                ->defaultSort('-created_at'),
            $request,
        );

        return $this->paginated(VideoResource::collection($videos));
    }

    public function show(Request $request, int $video): VideoResource
    {
        $this->authorizeApi($request, 'ViewAny:Video');

        return new VideoResource($this->visibleVideos($request)->findOrFail($video));
    }
}
