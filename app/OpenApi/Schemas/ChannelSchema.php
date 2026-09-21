<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Channel',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'creator_name', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'youtube_name', type: 'string', nullable: true),
        new OA\Property(property: 'is_video_reception_paused', type: 'boolean'),
        new OA\Property(property: 'show_on_homepage', type: 'boolean'),
        new OA\Property(property: 'logo_url', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
final class ChannelSchema
{
}
