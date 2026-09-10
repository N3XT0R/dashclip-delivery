<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Video\IsDeletableUseCase;
use App\Application\Video\UploadVideoUseCase;
use App\Http\Requests\Api\V1\StoreVideoRequest;
use App\Http\Requests\Api\V1\UpdateVideoRequest;
use App\Http\Resources\Api\V1\VideoResource;
use App\Repository\VideoRepository;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VideoController extends ApiController
{
    private const string RESPONSE_VIDEO_NOT_FOUND = 'Video not found or not visible to the user';
    private const string SCHEMA_VIDEO = '#/components/schemas/Video';
    private const string PATH_VIDEO_SHOW = '/api/v1/videos/{video}';

    public function __construct(private readonly VideoRepository $videoRepository)
    {
    }

    private function visibleVideos(Request $request): Builder
    {
        return $this->videoRepository->visibleForUser($this->apiUser($request));
    }

    #[OA\Get(
        path: '/api/v1/videos',
        description: 'Returns a paginated list of videos the authenticated user can see, the '
            . 'same set as in the Standard panel (videos the user submitted a clip for, or that '
            . 'belong to their team). Supports filtering by name and creation date range, plus '
            . 'sorting and pagination. The processing status is read-only (an internal ingest '
            . 'flag) and is not a filter.',
        summary: 'List videos visible to the authenticated user',
        security: [['oauth2' => ['videos:read']], ['bearerAuth' => []]],
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(
                name: 'filter[original_name]',
                description: 'Partial, case-insensitive match on the original file name',
                in: 'query',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[created_after]',
                description: 'ISO-8601 date-time lower bound (inclusive) on created_at',
                in: 'query',
                schema: new OA\Schema(type: 'string', format: 'date-time'),
            ),
            new OA\Parameter(
                name: 'filter[created_before]',
                description: 'ISO-8601 date-time upper bound (inclusive) on created_at',
                in: 'query',
                schema: new OA\Schema(type: 'string', format: 'date-time'),
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Comma-separated sort fields; prefix with "-" for descending. '
                    . 'Allowed: original_name, created_at, bytes. Default: -created_at',
                in: 'query',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(ref: '#/components/parameters/PageNumber'),
            new OA\Parameter(ref: '#/components/parameters/PageSize'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of videos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: self::SCHEMA_VIDEO),
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'filter.created_after' => ['sometimes', 'date'],
            'filter.created_before' => ['sometimes', 'date'],
        ]);

        return $this->paginatedList(
            QueryBuilder::for($this->visibleVideos($request))
                ->allowedFilters(
                    AllowedFilter::partial('original_name'),
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
            VideoResource::class,
            $request,
        );
    }

    #[OA\Get(
        path: self::PATH_VIDEO_SHOW,
        description: 'Returns one video by id, including its current processing status. Responds '
            . 'with 404 if the video does not exist or is not visible to the authenticated user.',
        summary: 'Show a single video',
        security: [['oauth2' => ['videos:read']], ['bearerAuth' => []]],
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(
                name: 'video',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The video',
                content: new OA\JsonContent(ref: self::SCHEMA_VIDEO),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_VIDEO_NOT_FOUND),
        ],
    )]
    public function show(Request $request, int $video): VideoResource
    {
        return new VideoResource($this->visibleVideos($request)->findOrFail($video));
    }

    #[OA\Post(
        path: '/api/v1/videos',
        description: 'Uploads a video file together with the initial clip boundaries '
            . '(start_sec/end_sec). The file is stored on the videos disk, a video record and '
            . 'its first clip are created for the user\'s default team, and the video is queued '
            . 'for the same ingest pipeline the Standard-panel upload uses (hashing, preview '
            . 'generation, duplicate detection). Returns 201 with a Location header; poll the '
            . 'show endpoint to follow the processing status.',
        summary: 'Upload a video through the existing ingest pipeline',
        security: [['oauth2' => ['videos:write']], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file', 'clip'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            description: 'Video file (mp4, mov, mkv); max size from the panel upload config',
                            type: 'string',
                            format: 'binary',
                        ),
                        new OA\Property(
                            property: 'clip',
                            required: ['start_sec', 'end_sec'],
                            properties: [
                                new OA\Property(property: 'start_sec', type: 'integer', minimum: 0),
                                new OA\Property(property: 'end_sec', type: 'integer'),
                            ],
                            type: 'object',
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ),
        tags: ['Videos'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Video uploaded and queued for processing',
                headers: [
                    new OA\Header(
                        header: 'Location',
                        description: 'URL of the created video',
                        schema: new OA\Schema(type: 'string'),
                    ),
                ],
                content: new OA\JsonContent(ref: self::SCHEMA_VIDEO),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function store(StoreVideoRequest $request, UploadVideoUseCase $uploadVideo): JsonResponse
    {
        $video = $uploadVideo->handle(
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

    #[OA\Patch(
        path: self::PATH_VIDEO_SHOW,
        description: 'Updates the display name (original_name) of a video the user owns. Does not '
            . 'touch the stored file or the ingest state. Responds with 404 for videos not '
            . 'visible to the user.',
        summary: 'Rename a video',
        security: [['oauth2' => ['videos:write']], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['original_name'],
                properties: [
                    new OA\Property(property: 'original_name', type: 'string', maxLength: 255),
                ],
                type: 'object',
            ),
        ),
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(
                name: 'video',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated video',
                content: new OA\JsonContent(ref: self::SCHEMA_VIDEO),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_VIDEO_NOT_FOUND),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function update(UpdateVideoRequest $request, int $video): VideoResource
    {
        $model = $this->visibleVideos($request)->findOrFail($video);
        $model->update($request->validated());

        return new VideoResource($model->refresh());
    }

    #[OA\Delete(
        path: self::PATH_VIDEO_SHOW,
        description: 'Permanently deletes a video the user owns, including its stored file. '
            . 'Only allowed while the video has no active (ready, non-expired) offers and no '
            . 'offer that was already picked up. This is the same rule the Standard-panel delete '
            . 'action enforces. Responds with 404 for videos not visible to the user, 409 when '
            . 'the video still has active or picked-up offers, and 204 on success.',
        summary: 'Delete a video',
        security: [['oauth2' => ['videos:delete']], ['bearerAuth' => []]],
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(
                name: 'video',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Video deleted'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_VIDEO_NOT_FOUND),
            new OA\Response(
                response: 409,
                description: 'The video still has active or picked-up offers and cannot be deleted',
            ),
        ],
    )]
    public function destroy(Request $request, int $video, IsDeletableUseCase $isDeletable): Response
    {
        $model = $this->visibleVideos($request)->findOrFail($video);

        abort_unless(
            $isDeletable->handle($model),
            Response::HTTP_CONFLICT,
            'The video still has active or picked-up offers and cannot be deleted.',
        );

        app(VideoService::class)->delete($model);

        return $this->noContent();
    }
}
