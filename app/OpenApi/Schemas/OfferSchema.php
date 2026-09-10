<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Offer',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'video_id', type: 'integer'),
        new OA\Property(property: 'channel_id', type: 'integer'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class OfferSchema
{
}
