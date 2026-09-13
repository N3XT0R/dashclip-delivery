<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use DOMDocument;
use App\Models\Channel;
use DOMXPath;
use Tests\DatabaseTestCase;

final class PublicWebsiteTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Channel::query()->update(['show_on_homepage' => false]);
        $this->withHeader('Accept-Language', 'de');
    }

    public function testPublicChannelListUsesDefaultsAndReflectsVisibilityChanges(): void
    {
        $zulu = Channel::factory()->create(['name' => 'Zulu public channel']);
        Channel::factory()->create(['name' => 'Alpha public channel', 'is_video_reception_paused' => true]);
        $this->assertTrue($zulu->fresh()->show_on_homepage);
        $this->get('/')->assertOk()->assertSeeInOrder(['Alpha public channel', 'Zulu public channel']);

        $zulu->update(['show_on_homepage' => false]);
        $this->get('/')->assertOk()->assertDontSee('Zulu public channel');
        $zulu->update(['show_on_homepage' => true]);
        $this->get('/')->assertOk()->assertSee('Zulu public channel');
    }

    public function testPublicChannelSectionIsHiddenWhenNoChannelsAreVisible(): void
    {
        Channel::factory()->create(['name' => 'Private channel', 'show_on_homepage' => false]);
        $this->get('/')->assertOk()
            ->assertDontSee('Private channel')
            ->assertDontSee(__('public.featured_channels'));
    }

    public function testPublicAssetsStayIsolatedAndPanelScriptsSupportBundledImports(): void
    {
        $document = new DOMDocument();
        @$document->loadHTML($this->get('/')->assertOk()->getContent());
        $xpath = new DOMXPath($document);
        $this->assertSame(0, $xpath->query('//script[contains(@src, "/app-")]')->length);
        foreach (['/standard/login', '/admin/login'] as $path) {
            $document = new DOMDocument();
            @$document->loadHTML($this->get($path)->assertOk()->getContent());
            $xpath = new DOMXPath($document);
            $this->assertGreaterThan(0, $xpath->query('//script[contains(@src, "/app-") and @type="module"]')->length);
            $this->assertSame(0, $xpath->query('//script[contains(@src, "/app-") and not(@type="module")]')->length);
            $this->assertSame(0, $xpath->query('//link[contains(@href, "/public-")]')->length);
        }
    }

    public function testHomepageProvidesUploadNavigationAndCuratedChannels(): void
    {
        Channel::factory()->create(['name' => 'Visible channel']);
        Channel::factory()->create(['name' => 'Hidden channel', 'show_on_homepage' => false]);
        $this->withoutVite();
        $response = $this->get('/')->assertOk()
            ->assertSee('Clips hochladen')
            ->assertSee(route('filament.standard.auth.register'), false)
            ->assertSee(route('filament.standard.auth.login'), false);

        $response->assertSee('Visible channel')->assertDontSee('Hidden channel');

        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $document->getElementsByTagName('h1')->length);
        $this->assertSame(0, $xpath->query('//a[contains(@href, "mailto:") or contains(@href, "/blog") or contains(@href, "/gallery")]')->length);
        $this->assertSame(1, $xpath->query('//button[@id="themeToggle" and @aria-label]')->length);
        $this->assertSame(1, $xpath->query('//nav//details/summary')->length);
        foreach ($xpath->query('//a[contains(@href, "#")]') as $anchor) {
            $fragment = parse_url($anchor->getAttribute('href'), PHP_URL_FRAGMENT);
            $this->assertSame(1, $xpath->query('//*[@id="'.$fragment.'"]')->length, $fragment);
        }
    }

    public function testMetadataAndHeroAreStableAndReadyForDiscovery(): void
    {
        $this->withoutVite();
        $document = new DOMDocument();
        @$document->loadHTML($this->get('/?campaign=test')->assertOk()->getContent());
        $xpath = new DOMXPath($document);
        foreach (['//meta[@name="description"]', '//link[@rel="canonical"]', '//meta[@property="og:image"]'] as $query) {
            $this->assertSame(1, $xpath->query($query)->length);
        }
        $this->assertSame(url('/'), $xpath->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $socialImage = $xpath->query('//meta[@property="og:image"]')->item(0)->getAttribute('content');
        $this->assertStringNotContainsString('?', $socialImage);
        $this->get('/')->assertSee($socialImage, false);
        $this->assertSame(1, $xpath->query('//main//img[@fetchpriority="high" and @loading="eager" and @width and @height and @alt=""]')->length);
        $this->get('/')->assertSee(route('datenschutz'), false)
            ->assertSee('Testinstanz - Nicht zur produktiven Verwendung');
    }
}
