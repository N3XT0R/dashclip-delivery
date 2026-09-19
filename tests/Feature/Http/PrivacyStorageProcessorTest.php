<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Constants\Config\PrivacyConfigEntry;
use App\Facades\Cfg;
use Tests\DatabaseTestCase;

/**
 * Verifies the configurable storage processor section of the privacy policy.
 */
final class PrivacyStorageProcessorTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function testSectionIsHiddenWithoutProviderName(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, 'https://example.com/datenschutz', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertDontSee('Speicherung bei einem Auftragsverarbeiter')
            ->assertDontSee('https://example.com/datenschutz', false);
    }

    public function testSectionNamesTheProviderAndTheAgreementWithoutALink(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_PROVIDER_NAME, 'Hetzner Online GmbH', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('Speicherung bei einem Auftragsverarbeiter')
            ->assertSee('bei Hetzner Online GmbH')
            ->assertSee('Art. 28 DSGVO')
            ->assertDontSee('Datenschutzhinweise des Anbieters');
    }

    public function testOptionalLinkPointsToTheProvidersInformation(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_PROVIDER_NAME, 'Hetzner Online GmbH', 'default');
        Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, 'https://example.com/datenschutz', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('Datenschutzhinweise des Anbieters')
            ->assertSee('href="https://example.com/datenschutz"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function testLinkCanBeAFileHostedByThePlatform(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_PROVIDER_NAME, 'Hetzner Online GmbH', 'default');
        Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, '/legal/hosting-info.pdf', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('href="/legal/hosting-info.pdf"', false);
    }

    public function testUnsafeLinksAreDroppedWhileTheSectionStays(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_PROVIDER_NAME, 'Hetzner Online GmbH', 'default');
        foreach (['javascript:alert(1)', '//evil.example/info', 'legal/info.pdf'] as $link) {
            Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, $link, 'default');

            $this->get('/datenschutz')->assertOk()
                ->assertSee('Speicherung bei einem Auftragsverarbeiter')
                ->assertDontSee('Datenschutzhinweise des Anbieters')
                ->assertDontSee('href="'.$link.'"', false);
        }
    }
}
