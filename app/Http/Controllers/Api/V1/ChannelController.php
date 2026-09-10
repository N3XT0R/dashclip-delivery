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
use Spatie\QueryBuilder\QueryBuilder;

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
        description: 'Returns a paginated list of channels the authenticated user operates '
            . '(channels they are a verified member of). Supports filtering by video-reception '
            . 'state, plus sorting and pagination. Admin-only fields such as weight and weekly '
            . 'quota are never exposed.',
        summary: 'List channels visible to the authenticated user',
        security: [['oauth2' => ['channels:read']], ['bearerAuth' => []]],
        tags: ['Channels'],
        parameters: [
            new OA\Parameter(
                name: 'filter[is_video_reception_paused]',
                description: 'Exact match',
                in: 'query',
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
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        return $this->paginatedList(
            QueryBuilder::for($this->visibleChannels($request))
                ->allowedFilters(AllowedFilter::exact('is_video_reception_paused'))
                ->allowedSorts('name', 'created_at')
                ->defaultSort('-created_at'),
            ChannelResource::class,
            $request,
        );
    }

    #[OA\Get(
        path: '/api/v1/channels/{channel}',
        description: 'Returns one channel the user operates, by id. Responds with 404 if the '
            . 'channel does not exist or the user has no access to it.',
        summary: 'Show a single channel',
        security: [['oauth2' => ['channels:read']], ['bearerAuth' => []]],
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
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_CHANNEL_NOT_FOUND),
        ],
    )]
    public function show(Request $request, int $channel): ChannelResource
    {
        return new ChannelResource($this->visibleChannels($request)->findOrFail($channel));
    }

    #[OA\Patch(
        path: '/api/v1/channels/{channel}',
        description: 'Toggles whether the channel currently accepts new video offers by setting '
            . 'is_video_reception_paused. This is the only channel field writable through the '
            . 'API; any other field in the body is rejected with 422. Responds with 404 for '
            . 'channels not visible to the user.',
        summary: 'Pause or resume video reception for a channel',
        security: [['oauth2' => ['channels:write']], ['bearerAuth' => []]],
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
                description: 'The updated channel',
                content: new OA\JsonContent(ref: self::SCHEMA_CHANNEL),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_CHANNEL_NOT_FOUND),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function update(UpdateChannelRequest $request, int $channel): ChannelResource
    {
        $model = $this->visibleChannels($request)->findOrFail($channel);
        $model->update($request->validated());

        return new ChannelResource($model->refresh());
    }
}
