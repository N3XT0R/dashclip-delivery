<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\LocaleDiscoveryService;
use Tests\TestCase;

final class LocaleDiscoveryServiceTest extends TestCase
{
    private LocaleDiscoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocaleDiscoveryService();
    }

    public function testListContainsAppLocales(): void
    {
        $result = $this->service->list();

        $this->assertContains('de', $result);
        $this->assertContains('en', $result);
    }

    public function testListExcludesVendorDirectory(): void
    {
        $result = $this->service->list();

        $this->assertNotContains('vendor', $result);
    }

    public function testLabelReturnsKnownDisplayName(): void
    {
        $this->assertSame('Deutsch', $this->service->label('de'));
        $this->assertSame('English', $this->service->label('en'));
    }

    public function testLabelFallsBackToUppercaseCodeForUnknownLocale(): void
    {
        $this->assertSame('FR', $this->service->label('fr'));
    }
}
