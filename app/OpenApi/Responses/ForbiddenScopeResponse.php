<?php

declare(strict_types=1);

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(response: 'ForbiddenScope', description: 'Missing scope')]
final class ForbiddenScopeResponse
{
}
