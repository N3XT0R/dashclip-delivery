<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Video
 */
class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'original_name' => $this->original_name,
            'ext' => $this->ext,
            'bytes' => $this->bytes,
            'human_readable_size' => $this->human_readable_size,
            'processing_status' => $this->processing_status?->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
