<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Root document for the Submitter API: the endpoints a clip submitter uses to
 * manage their own videos and teams.
 */
#[OA\Info(version: '1.0.0', title: 'Submitter API')]
#[OA\Server(url: '/', description: 'Application root')]
#[OA\SecurityScheme(
    securityScheme: 'oauth2',
    type: 'oauth2',
    description: OAuth2::DESCRIPTION,
    flows: [
        new OA\Flow(
            authorizationUrl: '/oauth/authorize',
            tokenUrl: '/oauth/token',
            refreshUrl: '/oauth/token/refresh',
            flow: 'authorizationCode',
            scopes: self::SCOPES,
        ),
    ],
)]
final class SubmitterApiSpec
{
    /**
     * OAuth2 scopes that gate the Submitter API endpoints.
     *
     * @var array<string, string>
     */
    public const array SCOPES = [
        'videos:read' => 'List and read videos',
        'videos:write' => 'Upload and rename videos',
        'videos:delete' => 'Delete videos',
        'teams:read' => 'List and read teams',
        'teams:write' => 'Create teams',
        'teams:delete' => 'Delete owned teams',
    ];
}
