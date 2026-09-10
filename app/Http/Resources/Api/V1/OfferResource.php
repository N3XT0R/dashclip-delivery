<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Assignment
 */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'status' => $this->status,
            'video_id' => $this->video_id,
            'channel_id' => $this->channel_id,
            'note' => $this->note,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
