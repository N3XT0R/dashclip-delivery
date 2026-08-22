<?php

declare(strict_types=1);

namespace App\OpenApi\Parameters;

use OpenApi\Attributes as OA;

#[OA\Parameter(
    parameter: 'PageNumber',
    name: 'page[number]',
    in: 'query',
    schema: new OA\Schema(type: 'integer', minimum: 1),
)]
#[OA\Parameter(
    parameter: 'PageSize',
    name: 'page[size]',
    in: 'query',
    schema: new OA\Schema(type: 'integer', minimum: 1),
)]
final class PaginationParameters
{
}
