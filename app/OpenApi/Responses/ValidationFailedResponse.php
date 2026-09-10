<?php

declare(strict_types=1);

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationFailed',
    description: 'Validation failed',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
)]
final class ValidationFailedResponse
{
}
