<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Builder;

class AssignmentQueryService implements AssignmentQueryInterface
{
    public function forChannel(Channel $channel): Builder
    {
        return Assignment::query()->where('channel_id', $channel->getKey());
    }

    public function available(): Builder
    {
        return Assignment::query()
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereIn('status', StatusEnum::getReadyStatus());
    }

    public function downloaded(): Builder
    {
        return Assignment::query()->where('status', StatusEnum::PICKEDUP->value);
    }

    public function expired(): Builder
    {
        return Assignment::query()
            ->where(function (Builder $query) {
                $query->where('status', StatusEnum::EXPIRED->value)
                    ->orWhere('expires_at', '<=', now());
            });
    }

    public function returned(): Builder
    {
        return Assignment::query()->where('status', StatusEnum::REJECTED->value);
    }
}
