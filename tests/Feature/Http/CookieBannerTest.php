<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\DatabaseTestCase;

final class CookieBannerTest extends DatabaseTestCase
{
    public function testBannerIsVisibleWithoutCookie(): void
    {
        $this->get(route('impressum'))
            ->assertOk()
            ->assertSee('cookie-banner', escape: false)
            ->assertSee('cookie-accept', escape: false);
    }

    public function testBannerIsHiddenAfterConsentCookieIsSet(): void
    {
        $this->withUnencryptedCookie('cookie_consent', 'true')
            ->get(route('impressum'))
            ->assertOk()
            ->assertDontSee('cookie-banner', escape: false)
            ->assertDontSee('cookie-accept', escape: false);
    }
}
