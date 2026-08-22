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

    public function show(Request $request, int $offer): OfferResource
    {
        return new OfferResource($this->visibleOffers($request)->findOrFail($offer));
    }

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

    public function comment(StoreOfferCommentRequest $request, int $offer): OfferResource
    {
        $model = $this->visibleOffers($request)->findOrFail($offer);
        $model->update(['note' => $request->validated('note')]);

        return new OfferResource($model->refresh());
    }
}
