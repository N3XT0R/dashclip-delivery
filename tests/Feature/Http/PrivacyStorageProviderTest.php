<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Constants\Config\PrivacyConfigEntry;
use App\Facades\Cfg;
use Tests\DatabaseTestCase;

/**
 * The privacy policy names the storage provider once it is configured.
 */
final class PrivacyStorageProviderTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function testSectionIsHiddenWithoutAProvider(): void
    {
        $this->get('/datenschutz')->assertOk()->assertDontSee('Speicherung bei einem Auftragsverarbeiter');
    }

    public function testSectionNamesTheProviderAndTheAgreement(): void
    {
        Cfg::set(PrivacyConfigEntry::STORAGE_PROVIDER_NAME, 'Hetzner Online GmbH (Serverstandort Falkenstein)', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('Speicherung bei einem Auftragsverarbeiter')
            ->assertSee('Hetzner Online GmbH (Serverstandort Falkenstein)')
            ->assertSee('Art. 28 DSGVO');
    }
}
