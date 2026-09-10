<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Root document for the Channel Operator API: the endpoints a channel operator
 * uses to manage their channels and the offers made to them.
 */
#[OA\Info(version: '1.0.0', title: 'Channel Operator API')]
#[OA\Server(url: '/', description: 'Application root')]
#[OA\SecurityScheme(
    securityScheme: 'oauth2',
    type: 'oauth2',
    description: OAuth2::DESCRIPTION,
    flows: [
        new OA\Flow(
            authorizationUrl: '/oauth/authorize',
            tokenUrl: '/oauth/token',
            refreshUrl: '/oauth/token',
            flow: 'authorizationCode',
            scopes: self::SCOPES,
        ),
    ],
)]
final class ChannelOperatorApiSpec
{
    /**
     * OAuth2 scopes that gate the Channel Operator API endpoints.
     *
     * @var array<string, string>
     */
    public const array SCOPES = [
        'channels:read' => 'List and read channels',
        'channels:write' => 'Update channel settings',
        'offers:read' => 'List and read offers',
        'offers:write' => 'Create offers and set comments',
    ];
}
