<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Shared bearer-token security scheme, scanned into both API documentations.
 */
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Paste a raw access token: a Personal Access Token created in the Standard '
        . 'panel, or a token obtained through the device grant.',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
final class BearerAuthScheme
{
}
