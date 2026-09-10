<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Offer\CreateOfferUseCase;
use App\Http\Requests\Api\V1\StoreOfferCommentRequest;
use App\Http\Requests\Api\V1\StoreOfferRequest;
use App\Http\Resources\Api\V1\OfferResource;
use App\Repository\AssignmentRepository;
use App\Repository\ChannelRepository;
use App\Repository\VideoRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\QueryBuilder\AllowedFilter;

class OfferController extends ApiController
{
    private const string SCHEMA_OFFER = '#/components/schemas/Offer';
    private const string RESPONSE_OFFER_NOT_FOUND = 'Offer not found or not visible to the user';

    public function __construct(
        private readonly VideoRepository $videoRepository,
        private readonly ChannelRepository $channelRepository,
        private readonly AssignmentRepository $assignmentRepository,
    ) {
    }

    private function visibleOffers(Request $request): Builder
    {
        return $this->assignmentRepository->visibleForUser($this->apiUser($request));
    }

    #[OA\Get(
        path: '/api/v1/offers',
        summary: 'List offers visible to the authenticated user',
        security: [['passport' => ['offers:read']]],
        tags: ['Offers'],
        parameters: [
            new OA\Parameter(
                name: 'filter[status]',
                in: 'query',
                description: 'Exact match',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'filter[channel_id]',
                in: 'query',
                description: 'Exact match',
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                description: 'Comma-separated sort fields; prefix with "-" for descending. '
                    . 'Allowed: created_at, expires_at. Default: -created_at',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(ref: '#/components/parameters/PageNumber'),
            new OA\Parameter(ref: '#/components/parameters/PageSize'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of offers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: self::SCHEMA_OFFER),
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
            $this->visibleOffers($request),
            OfferResource::class,
            [
                AllowedFilter::exact('status'),
                AllowedFilter::exact('channel_id'),
            ],
            ['created_at', 'expires_at'],
            '-created_at',
            $request,
        );
    }

    #[OA\Get(
        path: '/api/v1/offers/{offer}',
        summary: 'Show a single offer',
        security: [['passport' => ['offers:read']]],
        tags: ['Offers'],
        parameters: [
            new OA\Parameter(
                name: 'offer',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The offer',
                content: new OA\JsonContent(ref: self::SCHEMA_OFFER),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, description: self::RESPONSE_OFFER_NOT_FOUND),
        ],
    )]
    public function show(Request $request, int $offer): OfferResource
    {
        return new OfferResource($this->visibleOffers($request)->findOrFail($offer));
    }

    #[OA\Post(
        path: '/api/v1/offers',
        summary: 'Create an offer for a video/channel pair',
        security: [['passport' => ['offers:write']]],
        tags: ['Offers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['video_id', 'channel_id'],
                properties: [
                    new OA\Property(property: 'video_id', type: 'integer'),
                    new OA\Property(property: 'channel_id', type: 'integer'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Offer created',
                headers: [
                    new OA\Header(
                        header: 'Location',
                        description: 'URL of the created offer',
                        schema: new OA\Schema(type: 'string'),
                    ),
                ],
                content: new OA\JsonContent(ref: self::SCHEMA_OFFER),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ],
    )]
    public function store(StoreOfferRequest $request, CreateOfferUseCase $createOffer): JsonResponse
    {
        $user = $this->apiUser($request);
        $video = $this->videoRepository->visibleForUser($user)->findOrFail($request->validated('video_id'));
        $channel = $this->channelRepository->visibleForUser($user)->findOrFail($request->validated('channel_id'));

        $offer = $createOffer->handle($video, $channel);

        return (new OfferResource($offer))
            ->response($request)
            ->setStatusCode(201)
            ->header('Location', route('api.v1.offers.show', ['offer' => $offer->getKey()]));
    }

    #[OA\Post(
        path: '/api/v1/offers/{offer}/comment',
        summary: 'Set the comment note on an offer',
        security: [['passport' => ['offers:write']]],
        tags: ['Offers'],
        parameters: [
            new OA\Parameter(
                name: 'offer',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['note'],
                properties: [
                    new OA\Property(property: 'note', type: 'string', maxLength: 1000),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated offer',
                content: new OA\JsonContent(ref: self::SCHEMA_OFFER),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, description: self::RESPONSE_OFFER_NOT_FOUND),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ],
    )]
    public function comment(StoreOfferCommentRequest $request, int $offer): OfferResource
    {
        $model = $this->visibleOffers($request)->findOrFail($offer);
        $model->update(['note' => $request->validated('note')]);

        return new OfferResource($model->refresh());
    }
}
