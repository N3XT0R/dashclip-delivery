<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpdateChannelRequest;
use App\Http\Resources\Api\V1\ChannelResource;
use App\Repository\ChannelRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ChannelController extends ApiController
{
    public function __construct(private readonly ChannelRepository $channelRepository)
    {
    }

    private function visibleChannels(Request $request): Builder
    {
        return $this->channelRepository->visibleForUser($this->apiUser($request));
    }

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

    public function show(Request $request, int $channel): ChannelResource
    {
        return new ChannelResource($this->visibleChannels($request)->findOrFail($channel));
    }

    public function update(UpdateChannelRequest $request, int $channel): ChannelResource
    {
        $model = $this->visibleChannels($request)->findOrFail($channel);
        $model->update($request->validated());

        return new ChannelResource($model->refresh());
    }
}
