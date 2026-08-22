<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use N3XT0R\LaravelPassportAuthorizationCore\Application\UseCases\Grant\GetAllowedGrantTypeOptions;
use Tests\DatabaseTestCase;

final class AllowedGrantTypesTest extends DatabaseTestCase
{
    public function testLegacyGrantTypesAreExcluded(): void
    {
        $keys = app(GetAllowedGrantTypeOptions::class)->execute()->keys();

        $this->assertNotContains('password', $keys);
        $this->assertNotContains('implicit', $keys);
    }

    public function testExpectedGrantTypesAreAllowed(): void
    {
        $keys = app(GetAllowedGrantTypeOptions::class)->execute()->keys();

        $this->assertEqualsCanonicalizing(
            ['authorization_code', 'client_credentials', 'personal_access', 'device'],
            $keys->all()
        );
    }
}
