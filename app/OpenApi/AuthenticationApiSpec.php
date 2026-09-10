<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Root document for the Authentication API: the OAuth2 endpoints that issue the
 * access tokens the Submitter and Channel Operator APIs expect.
 */
#[OA\Info(
    version: '1.0.0',
    description: <<<'TXT'
        This interface follows the **OAuth 2.0** standard. If you have not worked with it
        before, [oauth.net/2](https://oauth.net/2/) is a good introduction.

        These endpoints obtain and refresh access tokens. Supported grant types: authorization
        code, client credentials, refresh token and device. A personal access token (created in
        the Standard panel) is issued outside these endpoints but works the same way once you
        have it. The resulting token is sent as `Authorization: Bearer <token>` to the Submitter
        or Channel Operator API.
        TXT,
    title: 'Authentication (OAuth2)',
)]
#[OA\Server(url: '/', description: 'Application root')]
#[OA\ExternalDocumentation(description: 'The OAuth 2.0 standard', url: 'https://oauth.net/2/')]
final class AuthenticationApiSpec
{
}
