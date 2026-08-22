<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Api\CreateOfferUseCase;
use App\Http\Requests\Api\V1\StoreOfferCommentRequest;
use App\Http\Requests\Api\V1\StoreOfferRequest;
use App\Http\Resources\Api\V1\OfferResource;
use App\Models\Assignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OfferController extends ApiController
{
    private function visibleOffers(Request $request): Builder
    {
        $user = $this->apiUser($request);

        return Assignment::query()->where(function (Builder $query) use ($user): void {
            $query->whereHas('channel', static function (Builder $channel) use ($user): void {
                $channel->userHasAccess($user);
            })->orWhere(static function (Builder $inner) use ($user): void {
                $inner->hasUsersClips($user);
            });
        });
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
        $offer = $createOffer->execute(
            videoId: (int)$request->validated('video_id'),
            channelId: (int)$request->validated('channel_id'),
        );

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
