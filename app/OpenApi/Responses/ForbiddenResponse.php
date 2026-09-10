<?php

declare(strict_types=1);

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(response: 'Forbidden', description: 'Missing scope or permission')]
final class ForbiddenResponse
{
}
