<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

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
    private const string RESPONSE_MISSING_SCOPE_OR_PERMISSION = 'Missing scope or permission';
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
        summary: 'List videos visible to the authenticated user',
        security: [['passport' => ['videos:read']]],
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(
                name: 'filter[original_name]',
                in: 'query',
                description: 'Partial, case-insensitive match on the original file name',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[processing_status]',
                in: 'query',
                description: 'Exact match',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['pending', 'running', 'completed', 'failed', 'deleted'],
                ),
            ),
            new OA\Parameter(
                name: 'filter[created_after]',
                in: 'query',
                description: 'ISO-8601 date-time lower bound (inclusive) on created_at',
                schema: new OA\Schema(type: 'string', format: 'date-time'),
            ),
            new OA\Parameter(
                name: 'filter[created_before]',
                in: 'query',
                description: 'ISO-8601 date-time upper bound (inclusive) on created_at',
                schema: new OA\Schema(type: 'string', format: 'date-time'),
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                description: 'Comma-separated sort fields; prefix with "-" for descending. '
                    . 'Allowed: original_name, created_at, bytes. Default: -created_at',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'page[number]',
                in: 'query',
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
            new OA\Parameter(
                name: 'page[size]',
                in: 'query',
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
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
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE_OR_PERMISSION),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'filter.created_after' => ['sometimes', 'date'],
            'filter.created_before' => ['sometimes', 'date'],
        ]);

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

    #[OA\Get(
        path: self::PATH_VIDEO_SHOW,
        summary: 'Show a single video',
        security: [['passport' => ['videos:read']]],
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE_OR_PERMISSION),
            new OA\Response(response: 404, description: self::RESPONSE_VIDEO_NOT_FOUND),
        ],
    )]
    public function show(Request $request, int $video): VideoResource
    {
        return new VideoResource($this->visibleVideos($request)->findOrFail($video));
    }

    #[OA\Post(
        path: '/api/v1/videos',
        summary: 'Upload a video through the existing ingest pipeline',
        security: [['passport' => ['videos:write']]],
        tags: ['Videos'],
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE_OR_PERMISSION),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
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
        summary: 'Rename a video',
        security: [['passport' => ['videos:write']]],
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(
                name: 'video',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
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
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated video',
                content: new OA\JsonContent(ref: self::SCHEMA_VIDEO),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE_OR_PERMISSION),
            new OA\Response(response: 404, description: self::RESPONSE_VIDEO_NOT_FOUND),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
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
        summary: 'Delete a video',
        security: [['passport' => ['videos:delete']]],
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE_OR_PERMISSION),
            new OA\Response(response: 404, description: self::RESPONSE_VIDEO_NOT_FOUND),
        ],
    )]
    public function destroy(Request $request, int $video): Response
    {
        $model = $this->visibleVideos($request)->findOrFail($video);
        app(VideoService::class)->delete($model);

        return $this->noContent();
    }
}
