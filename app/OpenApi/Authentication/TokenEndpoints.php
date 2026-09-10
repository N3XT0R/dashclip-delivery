<?php

declare(strict_types=1);

namespace App\OpenApi\Authentication;

use OpenApi\Attributes as OA;

/**
 * Documentation for the OAuth2 token endpoint.
 */
final class TokenEndpoints
{
    #[OA\Post(
        path: '/oauth/token',
        description: <<<'TXT'
            Issues an access token. The `grant_type` field selects the flow:

            - **authorization_code**: exchange the `code` from `/oauth/authorize` (send
              `code`, `redirect_uri`, `client_id`, `client_secret` for confidential clients,
              and `code_verifier` when PKCE was used). Returns an access token and a
              refresh token.
            - **client_credentials**: machine-to-machine (`client_id`, `client_secret`,
              optional `scope`). No user context, so the resource APIs return 403 or empty
              lists for these tokens, so this grant is documented but not useful here.
            - **refresh_token**: exchange a previous `refresh_token` (`client_id`,
              `client_secret`, optional `scope`) for a new access token.

            Personal access tokens are created in the Standard panel, not here. Device
            tokens are polled here with `grant_type=urn:ietf:params:oauth:grant-type:device_code`.
            TXT,
        summary: 'Issue or refresh an access token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['grant_type'],
                    properties: [
                        new OA\Property(
                            property: 'grant_type',
                            type: 'string',
                            enum: [
                                'authorization_code',
                                'client_credentials',
                                'refresh_token',
                                'urn:ietf:params:oauth:grant-type:device_code',
                            ],
                        ),
                        new OA\Property(property: 'client_id', type: 'string'),
                        new OA\Property(property: 'client_secret', description: 'Confidential clients only.', type: 'string'),
                        new OA\Property(property: 'code', description: 'authorization_code grant.', type: 'string'),
                        new OA\Property(property: 'redirect_uri', description: 'authorization_code grant.', type: 'string'),
                        new OA\Property(property: 'code_verifier', description: 'authorization_code grant with PKCE.', type: 'string'),
                        new OA\Property(property: 'refresh_token', description: 'refresh_token grant.', type: 'string'),
                        new OA\Property(property: 'device_code', description: 'device_code grant.', type: 'string'),
                        new OA\Property(property: 'scope', description: 'Space-separated scopes.', type: 'string'),
                    ],
                    type: 'object',
                ),
            ),
        ),
        tags: ['OAuth2'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token issued',
                content: new OA\JsonContent(ref: '#/components/schemas/OAuthToken'),
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request or grant',
                content: new OA\JsonContent(ref: '#/components/schemas/OAuthError'),
            ),
            new OA\Response(
                response: 401,
                description: 'Client authentication failed',
                content: new OA\JsonContent(ref: '#/components/schemas/OAuthError'),
            ),
        ],
    )]
    public function token(): void
    {
    }

    #[OA\Post(
        path: '/oauth/token/refresh',
        description: 'Refreshes the short-lived token cookie used by the first-party SPA flow '
            . '(`CreateFreshApiToken`). This is a web-session endpoint (CSRF-protected), not the '
            . 'OAuth2 refresh_token grant. For that, call `/oauth/token` with '
            . '`grant_type=refresh_token`.',
        summary: 'Refresh the transient first-party token cookie',
        tags: ['OAuth2'],
        responses: [
            new OA\Response(response: 200, description: 'Cookie refreshed'),
            new OA\Response(response: 401, description: 'No authenticated web session'),
        ],
    )]
    public function refresh(): void
    {
    }
}
