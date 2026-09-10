<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpdateChannelRequest;
use App\Http\Resources\Api\V1\ChannelResource;
use App\Repository\ChannelRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;

class ChannelController extends ApiController
{
    private const string SCHEMA_CHANNEL = '#/components/schemas/Channel';
    private const string RESPONSE_CHANNEL_NOT_FOUND = 'Channel not found or not visible to the user';

    public function __construct(private readonly ChannelRepository $channelRepository)
    {
    }

    private function visibleChannels(Request $request): Builder
    {
        return $this->channelRepository->visibleForUser($this->apiUser($request));
    }

    #[OA\Get(
        path: '/api/v1/channels',
        summary: 'List channels visible to the authenticated user',
        security: [['passport' => ['channels:read']]],
        tags: ['Channels'],
        parameters: [
            new OA\Parameter(
                name: 'filter[is_video_reception_paused]',
                in: 'query',
                description: 'Exact match',
                schema: new OA\Schema(type: 'boolean'),
            ),
            new OA\Parameter(ref: '#/components/parameters/SortNameCreatedAt'),
            new OA\Parameter(ref: '#/components/parameters/PageNumber'),
            new OA\Parameter(ref: '#/components/parameters/PageSize'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of channels',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: self::SCHEMA_CHANNEL),
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->paginatedList(
            $this->visibleChannels($request),
            ChannelResource::class,
            [AllowedFilter::exact('is_video_reception_paused')],
            ['name', 'created_at'],
            '-created_at',
            $request,
        );
    }

    #[OA\Get(
        path: '/api/v1/channels/{channel}',
        summary: 'Show a single channel',
        security: [['passport' => ['channels:read']]],
        tags: ['Channels'],
        parameters: [
            new OA\Parameter(
                name: 'channel',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The channel',
                content: new OA\JsonContent(ref: self::SCHEMA_CHANNEL),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, description: self::RESPONSE_CHANNEL_NOT_FOUND),
        ],
    )]
    public function show(Request $request, int $channel): ChannelResource
    {
        return new ChannelResource($this->visibleChannels($request)->findOrFail($channel));
    }

    #[OA\Patch(
        path: '/api/v1/channels/{channel}',
        summary: 'Pause or resume video reception for a channel',
        security: [['passport' => ['channels:write']]],
        tags: ['Channels'],
        parameters: [
            new OA\Parameter(
                name: 'channel',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['is_video_reception_paused'],
                properties: [
                    new OA\Property(property: 'is_video_reception_paused', type: 'boolean'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated channel',
                content: new OA\JsonContent(ref: self::SCHEMA_CHANNEL),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, description: self::RESPONSE_CHANNEL_NOT_FOUND),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ],
    )]
    public function update(UpdateChannelRequest $request, int $channel): ChannelResource
    {
        $model = $this->visibleChannels($request)->findOrFail($channel);
        $model->update($request->validated());

        return new ChannelResource($model->refresh());
    }
}
