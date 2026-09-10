<?php

declare(strict_types=1);

namespace App\OpenApi\Authentication;

use OpenApi\Attributes as OA;

/**
 * Documentation for the OAuth2 authorization-code consent endpoints.
 */
final class AuthorizationEndpoints
{
    #[OA\Get(
        path: '/oauth/authorize',
        description: 'Starts the authorization_code flow. Redirect the user\'s browser here with '
            . '`response_type=code`, `client_id`, `redirect_uri`, `scope` (space-separated) and '
            . '`state`; add `code_challenge` + `code_challenge_method=S256` for PKCE. The authorization server '
            . 'renders a consent screen; on approval the user is redirected back to '
            . '`redirect_uri` with `code` and `state`. Requires an authenticated web session.',
        summary: 'Authorization request (consent screen)',
        tags: ['OAuth2'],
        parameters: [
            new OA\Parameter(name: 'response_type', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['code'])),
            new OA\Parameter(name: 'client_id', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'redirect_uri', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'scope', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'state', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code_challenge', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'code_challenge_method', in: 'query', schema: new OA\Schema(type: 'string', enum: ['S256'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Consent screen (HTML)'),
            new OA\Response(response: 302, description: 'Redirect to redirect_uri (auto-approved or error)'),
            new OA\Response(response: 401, description: 'No authenticated web session'),
        ],
    )]
    public function authorize(): void
    {
    }

    #[OA\Post(
        path: '/oauth/authorize',
        description: 'Approves the pending authorization request for the current web session and '
            . 'redirects back to the client\'s `redirect_uri` with `code` and `state`. '
            . 'CSRF-protected.',
        summary: 'Approve the authorization request',
        tags: ['OAuth2'],
        responses: [
            new OA\Response(response: 302, description: 'Redirect to redirect_uri with the authorization code'),
        ],
    )]
    public function approve(): void
    {
    }

    #[OA\Delete(
        path: '/oauth/authorize',
        description: 'Denies the pending authorization request and redirects back to the '
            . 'client\'s `redirect_uri` with an `access_denied` error. CSRF-protected.',
        summary: 'Deny the authorization request',
        tags: ['OAuth2'],
        responses: [
            new OA\Response(response: 302, description: 'Redirect to redirect_uri with error=access_denied'),
        ],
    )]
    public function deny(): void
    {
    }
}
