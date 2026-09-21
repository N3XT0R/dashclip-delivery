<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Channel\RemoveChannelLogoUseCase;
use App\Application\Channel\ReplaceChannelLogoUseCase;
use App\Application\Channel\UpdateChannelSettingsUseCase;
use App\Exceptions\Channel\ChannelLogoProcessingException;
use App\Http\Requests\Api\V1\UpdateChannelRequest;
use App\Http\Requests\Api\V1\UploadChannelLogoRequest;
use App\Http\Resources\Api\V1\ChannelResource;
use App\Repository\ChannelRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        description: 'Changes the settings of a channel the user operates: the same settings the '
            . 'channel settings page offers. Every field is optional, send only what should '
            . 'change; fields that are left out keep their value. `name` and `email` cannot be '
            . 'cleared and must not be used by another channel, `creator_name` and `youtube_name` '
            . 'can be cleared with `null`. Setting `is_video_reception_paused` to `true` stops new '
            . 'offers for the channel, `show_on_homepage` controls whether the channel is listed on '
            . 'the public homepage. The logo has its own endpoints. Any other field, an empty body '
            . 'or a taken name or email is rejected with 422. Responds with 404 for channels not '
            . 'visible to the user.',
        summary: 'Update channel settings',
        security: [['oauth2' => ['channels:write']], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'creator_name', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'youtube_name', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'is_video_reception_paused', type: 'boolean'),
                    new OA\Property(property: 'show_on_homepage', type: 'boolean'),
                ],
                type: 'object',
                minProperties: 1,
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
    public function update(
        UpdateChannelRequest $request,
        int $channel,
        UpdateChannelSettingsUseCase $updateSettings,
    ): ChannelResource {
        $model = $this->visibleChannels($request)->findOrFail($channel);

        return new ChannelResource($updateSettings->handle($model, $request->validated()));
    }

    #[OA\Post(
        path: '/api/v1/channels/{channel}/logo',
        description: 'Uploads a new logo for a channel the user operates and replaces the current '
            . 'one, which is deleted. Accepts PNG, JPEG or WebP up to 512 KB as multipart form '
            . 'field `logo`. Larger images are shrunk to fit into 256 x 256 pixels, keeping their '
            . 'aspect ratio and format; smaller images are stored as sent. The response contains '
            . 'the new `logo_url`. Responds with 422 for other file types, files over 512 KB or '
            . 'images that cannot be read, and with 404 for channels not visible to the user.',
        summary: 'Upload a channel logo',
        security: [['oauth2' => ['channels:write']], ['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['logo'],
                    properties: [
                        new OA\Property(property: 'logo', type: 'string', format: 'binary'),
                    ],
                    type: 'object',
                ),
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
                description: 'The channel with its new logo',
                content: new OA\JsonContent(ref: self::SCHEMA_CHANNEL),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_CHANNEL_NOT_FOUND),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function uploadLogo(
        UploadChannelLogoRequest $request,
        int $channel,
        ReplaceChannelLogoUseCase $replaceLogo,
    ): ChannelResource {
        $model = $this->visibleChannels($request)->findOrFail($channel);

        try {
            return new ChannelResource($replaceLogo->handle($model, $request->file('logo')));
        } catch (ChannelLogoProcessingException $exception) {
            throw ValidationException::withMessages(['logo' => $exception->getMessage()]);
        }
    }

    #[OA\Delete(
        path: '/api/v1/channels/{channel}/logo',
        description: 'Removes the logo of a channel the user operates and deletes the file. The '
            . 'channel is then shown without a logo and `logo_url` is `null`. Calling it for a '
            . 'channel without a logo succeeds as well. Responds with 404 for channels not visible '
            . 'to the user.',
        summary: 'Remove a channel logo',
        security: [['oauth2' => ['channels:write']], ['bearerAuth' => []]],
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
                description: 'The channel without logo',
                content: new OA\JsonContent(ref: self::SCHEMA_CHANNEL),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(response: 404, description: self::RESPONSE_CHANNEL_NOT_FOUND),
        ],
    )]
    public function removeLogo(Request $request, int $channel, RemoveChannelLogoUseCase $removeLogo): ChannelResource
    {
        $model = $this->visibleChannels($request)->findOrFail($channel);

        return new ChannelResource($removeLogo->handle($model));
    }
}
