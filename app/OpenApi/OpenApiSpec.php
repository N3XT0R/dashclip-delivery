<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'Dashclip Delivery API')]
#[OA\Server(url: '/api/v1', description: 'REST API v1')]
#[OA\SecurityScheme(
    securityScheme: 'passport',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'OAuth2 access token issued by Laravel Passport',
)]
final class OpenApiSpec
{
}
