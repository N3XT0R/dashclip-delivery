<?php

declare(strict_types=1);

namespace App\OpenApi\Authentication;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OAuthToken',
    description: 'A successful token response from the token endpoint.',
    properties: [
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_in', description: 'Lifetime of the access token in seconds.', type: 'integer'),
        new OA\Property(property: 'access_token', type: 'string'),
        new OA\Property(
            property: 'refresh_token',
            description: 'Only returned for the authorization_code grant.',
            type: 'string',
            nullable: true,
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'OAuthError',
    description: 'An RFC 6749 error response.',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'invalid_grant'),
        new OA\Property(property: 'error_description', type: 'string'),
        new OA\Property(property: 'hint', type: 'string', nullable: true),
        new OA\Property(property: 'message', type: 'string'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'DeviceCode',
    description: 'The device authorization response.',
    properties: [
        new OA\Property(property: 'device_code', type: 'string'),
        new OA\Property(property: 'user_code', description: 'Short code the user types at the verification URI.', type: 'string'),
        new OA\Property(property: 'verification_uri', type: 'string'),
        new OA\Property(property: 'verification_uri_complete', type: 'string'),
        new OA\Property(property: 'expires_in', type: 'integer'),
        new OA\Property(property: 'interval', description: 'Minimum seconds between token-endpoint polls.', type: 'integer'),
    ],
    type: 'object',
)]
final class AuthSchemas
{
}
