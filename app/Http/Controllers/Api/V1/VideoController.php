<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Api\UploadVideoUseCase;
use App\Http\Requests\Api\V1\StoreVideoRequest;
use App\Http\Requests\Api\V1\UpdateVideoRequest;
use App\Http\Resources\Api\V1\VideoResource;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function store(StoreVideoRequest $request, UploadVideoUseCase $uploadVideo): JsonResponse
    {
        $this->authorizeApi($request, 'Create:Video');

        $video = $uploadVideo->execute(
            file: $request->file('file'),
            startSec: (int)$request->validated('clip.start_sec'),
            endSec: (int)$request->validated('clip.end_sec'),
            user: $this->apiUser($request),
        );

        return (new VideoResource($video))
            ->response($request)
            ->setStatusCode(201)
            ->header('Location', route('api.v1.videos.show', ['video' => $video->getKey()]));
    }

    public function update(UpdateVideoRequest $request, int $video): VideoResource
    {
        $this->authorizeApi($request, 'Update:Video');
        $model = $this->visibleVideos($request)->findOrFail($video);
        $model->update($request->validated());

        return new VideoResource($model->refresh());
    }

    public function destroy(Request $request, int $video): Response
    {
        $this->authorizeApi($request, 'Delete:Video');
        $model = $this->visibleVideos($request)->findOrFail($video);
        app(VideoService::class)->delete($model);

        return $this->noContent();
    }
}
