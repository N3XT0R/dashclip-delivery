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
#[OA\Parameter(
    parameter: 'SortNameCreatedAt',
    name: 'sort',
    in: 'query',
    description: 'Comma-separated sort fields; prefix with "-" for descending. '
        . 'Allowed: name, created_at. Default: -created_at',
    schema: new OA\Schema(type: 'string'),
)]
final class PaginationParameters
{
}
