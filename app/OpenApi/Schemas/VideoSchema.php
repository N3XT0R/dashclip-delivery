<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Video',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'original_name', type: 'string'),
        new OA\Property(property: 'ext', type: 'string'),
        new OA\Property(property: 'bytes', type: 'integer'),
        new OA\Property(property: 'human_readable_size', type: 'string'),
        new OA\Property(
            property: 'processing_status',
            type: 'string',
            enum: ['pending', 'running', 'completed', 'failed', 'deleted'],
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
)]
final class VideoSchema
{
}
