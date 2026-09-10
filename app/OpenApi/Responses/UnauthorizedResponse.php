<?php

declare(strict_types=1);

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(response: 'Unauthorized', description: 'Unauthenticated')]
final class UnauthorizedResponse
{
}
