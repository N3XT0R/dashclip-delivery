<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Channel
 */
class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'creator_name' => $this->creator_name,
            'email' => $this->email,
            'youtube_name' => $this->youtube_name,
            'is_video_reception_paused' => $this->is_video_reception_paused,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
