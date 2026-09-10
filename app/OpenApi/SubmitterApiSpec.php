<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Root document for the Submitter API — the endpoints a clip submitter uses to
 * manage their own videos and teams.
 */
#[OA\Info(version: '1.0.0', title: 'Dashclip Delivery — Submitter API')]
#[OA\Server(url: '/', description: 'Application root')]
#[OA\SecurityScheme(
    securityScheme: 'passport',
    type: 'oauth2',
    description: <<<'TXT'
        OAuth2 via Laravel Passport. Enabled grant types and how to obtain a token for testing:

        - **authorization_code** — use the flow below (redirects to `/oauth/authorize`,
          exchanges at `/oauth/token`). Produces a user-scoped token; this is the flow to use
          for trying out the endpoints interactively.
        - **personal_access** — create a Personal Access Token in the Standard panel
          ("OAuth" → "Personal Access Tokens") and paste it into the **bearerAuth** scheme.
          Fastest path, no client setup required.
        - **device** — request a device code at `/oauth/device/code`, confirm it at
          `/oauth/device`, poll `/oauth/token`, then paste the resulting token into
          **bearerAuth**.
        - **client_credentials** — also enabled at the Passport level, but such tokens carry
          no user context, so every (user-scoped) endpoint here returns 403 or an empty list.
          Not offered as an interactive flow for that reason.

        Scopes map to `<resource>:<action>` and must be granted to the acting user via the
        client's scope-grant configuration.
        TXT,
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
