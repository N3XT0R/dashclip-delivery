<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Constants\Config\DefaultConfigEntry;
use App\Facades\Cfg;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Tests\DatabaseTestCase;

/**
 * The privacy policy mentions Dropbox only while videos are actually stored there.
 */
final class PrivacyDropboxSectionTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function testDropboxIsExplainedWhileItIsTheConfiguredStorage(): void
    {
        Cfg::set(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'dropbox', 'default');

        $this->get('/datenschutz')->assertOk()
            ->assertSee('Dropbox-Integration')
            ->assertSee('Die Einwilligung zur Nutzung der Dropbox-Integration ist zwingend erforderlich', false);
    }

    public function testDropboxIsExplainedWhileVideosRemainThere(): void
    {
        Cfg::set(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'hetzner', 'default');
        Video::factory()->create(['disk' => 'dropbox']);

        $this->get('/datenschutz')->assertOk()->assertSee('Dropbox-Integration');
    }

    public function testDropboxDisappearsOnceItIsNoLongerUsed(): void
    {
        Cfg::set(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'hetzner', 'default');
        Video::factory()->create(['disk' => 'hetzner']);

        $this->get('/datenschutz')->assertOk()
            ->assertDontSee('Dropbox')
            ->assertSee('Widerruf der Einwilligung')
            ->assertSee('Die Verarbeitung Ihrer Inhalte ist für die Nutzung der Plattform erforderlich', false);
    }
}
