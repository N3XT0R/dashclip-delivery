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
use Spatie\QueryBuilder\QueryBuilder;

class OfferController extends ApiController
{
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
                description: 'Paginated list of offers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Offer'),
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
            new OA\Response(response: 403, description: 'Missing scope or permission'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $offers = $this->paginate(
            QueryBuilder::for($this->visibleOffers($request))
                ->allowedFilters(
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('channel_id'),
                )
                ->allowedSorts('created_at', 'expires_at')
                ->defaultSort('-created_at'),
            $request,
        );

        return $this->paginated(OfferResource::collection($offers));
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
                content: new OA\JsonContent(ref: '#/components/schemas/Offer'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing scope or permission'),
            new OA\Response(response: 404, description: 'Offer not found or not visible to the user'),
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
                content: new OA\JsonContent(ref: '#/components/schemas/Offer'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing scope or permission'),
            new OA\Response(
                response: 422,
                description: 'Validation failed, or video/channel not accessible',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
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
                content: new OA\JsonContent(ref: '#/components/schemas/Offer'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Missing scope or permission'),
            new OA\Response(response: 404, description: 'Offer not found or not visible to the user'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
    )]
    public function comment(StoreOfferCommentRequest $request, int $offer): OfferResource
    {
        $model = $this->visibleOffers($request)->findOrFail($offer);
        $model->update(['note' => $request->validated('note')]);

        return new OfferResource($model->refresh());
    }
}
