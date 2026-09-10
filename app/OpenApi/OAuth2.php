<?php

declare(strict_types=1);

namespace App\OpenApi;

/**
 * Shared copy for the OAuth2 security scheme used by both resource APIs.
 */
final class OAuth2
{
    public const string DESCRIPTION = <<<'TXT'
        OAuth2 access tokens. How to obtain one for interactive testing:

        - **authorization code**: run the flow below (the browser is redirected to
          `/oauth/authorize`, the code is exchanged at `/oauth/token`). This produces a
          user-scoped token and is the one to use for trying the endpoints out.
        - **personal access token**: create one in the Standard panel
          ("OAuth" → "Personal Access Tokens") and paste it into the `bearerAuth` scheme.
          This is the fastest path and needs no client setup.
        - **device**: request a code at `/oauth/device/code`, confirm it at `/oauth/device`,
          poll `/oauth/token`, then paste the token into `bearerAuth`.
        - **client credentials**: also supported, but these tokens have no user context, so
          every endpoint here returns 403 or an empty list. It is therefore not offered as an
          interactive flow.

        Scopes are `<resource>:<action>` and must be granted to the acting user.
        TXT;
}
