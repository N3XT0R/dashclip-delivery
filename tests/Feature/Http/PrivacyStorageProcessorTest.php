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

    public function testSectionIsHiddenWithoutAgreementLink(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_PROVIDER_NAME, 'Hetzner Online GmbH', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertDontSee('Speicherung bei einem Auftragsverarbeiter')
            ->assertDontSee('Hetzner Online GmbH');
    }

    public function testSectionNamesTheProviderAndLinksTheAgreement(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_PROVIDER_NAME, 'Hetzner Online GmbH', 'default');
        Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, 'https://example.com/avv.pdf', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('Speicherung bei einem Auftragsverarbeiter')
            ->assertSee('Hetzner Online GmbH')
            ->assertSee('Art. 28 DSGVO')
            ->assertSee('href="https://example.com/avv.pdf"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function testSectionFallsBackToAGenericProviderWithoutName(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, 'https://example.com/avv.pdf', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('bei unserem Speicher-Anbieter')
            ->assertSee('href="https://example.com/avv.pdf"', false);
    }

    public function testAgreementCanBeAFileHostedByThePlatform(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, '/legal/avv-hetzner.pdf', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('Speicherung bei einem Auftragsverarbeiter')
            ->assertSee('href="/legal/avv-hetzner.pdf"', false);
    }

    public function testUnsafeAgreementLinksAreNotRendered(): void
    {
        foreach (['javascript:alert(1)', '//evil.example/avv.pdf', 'legal/avv.pdf'] as $link) {
            Cfg::set(PrivacyConfigEntry::STORAGE_DPA_URL, $link, 'default');

            $this->get('/datenschutz')->assertOk()
                ->assertDontSee('Speicherung bei einem Auftragsverarbeiter')
                ->assertDontSee('href="'.$link.'"', false);
        }
    }
}
