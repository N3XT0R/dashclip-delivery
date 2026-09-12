<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

final class PublicWebsiteTest extends TestCase
{
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
        $this->withoutVite();
        $response = $this->get('/')->assertOk()
            ->assertSee('Clips hochladen')
            ->assertSee(route('filament.standard.auth.register'), false)
            ->assertSee(route('filament.standard.auth.login'), false)
            ->assertDontSee('DashboardHeroes')->assertDontSee('Dashboard Heroes');

        foreach (['RLP Dashcam', 'Lets Dashcam', 'Augen auf!', 'Road Rave Germany', 'NEDK - NOCH EIN DASHCAM KANAL', 'Dashcam Stories'] as $channel) {
            $response->assertSee($channel);
        }

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
