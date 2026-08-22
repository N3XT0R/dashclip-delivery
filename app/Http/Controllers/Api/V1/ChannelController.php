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
    private const string RESPONSE_MISSING_SCOPE_OR_PERMISSION = 'Missing scope or permission';
    private const string SCHEMA_CHANNEL = '#/components/schemas/Channel';

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
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                description: 'Comma-separated sort fields; prefix with "-" for descending. '
                    . 'Allowed: name, created_at. Default: -created_at',
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
                description: 'Paginated list of channels',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: self::SCHEMA_CHANNEL),
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
        $channels = $this->paginate(
            QueryBuilder::for($this->visibleChannels($request))
                ->allowedFilters(AllowedFilter::exact('is_video_reception_paused'))
                ->allowedSorts('name', 'created_at')
                ->defaultSort('-created_at'),
            $request,
        );

        return $this->paginated(ChannelResource::collection($channels));
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE_OR_PERMISSION),
            new OA\Response(response: 404, description: 'Channel not found or not visible to the user'),
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: self::RESPONSE_MISSING_SCOPE_OR_PERMISSION),
            new OA\Response(response: 404, description: 'Channel not found or not visible to the user'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function update(UpdateChannelRequest $request, int $channel): ChannelResource
    {
        $model = $this->visibleChannels($request)->findOrFail($channel);
        $model->update($request->validated());

        return new ChannelResource($model->refresh());
    }
}
