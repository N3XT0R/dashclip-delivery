<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use DOMDocument;
use DOMXPath;
use Tests\DatabaseTestCase;

final class PublicLocaleTest extends DatabaseTestCase
{
    public function testBrowserLanguageAndRegionalPreferencesAreApplied(): void
    {
        $this->withoutVite();
        $this->withHeader('Accept-Language', 'fr-FR;q=1,en-GB;q=0.9,de;q=0.5')
            ->get('/')->assertOk()->assertSee('<html lang="en">', false)
            ->assertSee('Upload clips')->assertSee('How it works')
            ->assertSee('What happens to my clip?')->assertSee('A ZIP file is a package containing multiple files.')
            ->assertDontSee('Clips hochladen')->assertHeader('Content-Language', 'en');
    }

    public function testUnsupportedAndMissingBrowserLanguagesFallBackToGerman(): void
    {
        $this->withoutVite();
        config()->set('app.locale', 'de');
        $this->withHeader('Accept-Language', '')->get('/')->assertOk()->assertSee('Clips hochladen');
        $this->withHeader('Accept-Language', 'ja-JP')->get('/')->assertSee('<html lang="de">', false);
    }

    public function testManualChoiceOverridesBrowserAndPersistsInAnEncryptedCookie(): void
    {
        $this->withoutVite();
        $response = $this->post(route('public.locale'), ['locale' => 'en', 'return_to' => '/?campaign=example']);
        $response->assertRedirect(url('/?campaign=example'))->assertCookie('public_locale', 'en');
        $cookie = $response->getCookie('public_locale', decrypt: false);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertGreaterThan(now()->addMonths(11)->timestamp, $cookie->getExpiresTime());
        $this->withUnencryptedCookie('public_locale', $cookie->getValue())
            ->withHeader('Accept-Language', 'de-DE')->get('/')->assertSee('Upload clips');
    }

    public function testLanguageSwitchPreservesSignedQueryAndRejectsExternalRedirects(): void
    {
        $return = '/offer/1/2?expires=123&signature=example';
        $this->post(route('public.locale'), ['locale' => 'de', 'return_to' => $return])->assertRedirect(url($return));
        foreach (['https://example.com', '//example.com', '/\\example.com', "/\nexample.com"] as $target) {
            $this->post(route('public.locale'), ['locale' => 'en', 'return_to' => $target])->assertSessionHasErrors('return_to');
        }
        $this->post(route('public.locale'), ['locale' => 'fr', 'return_to' => '/'])->assertSessionHasErrors('locale');
    }

    public function testBothLanguagesExposeAccessibleButtonsAndTheFullGuide(): void
    {
        $this->withoutVite();
        foreach (['de', 'en'] as $locale) {
            $response = $this->withHeader('Accept-Language', $locale)->get('/')->assertOk();
            $document = new DOMDocument();
            @$document->loadHTML($response->getContent());
            $xpath = new DOMXPath($document);
            $this->assertSame(2, $xpath->query('//header//button[@name="locale"]')->length);
            $this->assertSame(1, $xpath->query('//header//button[@name="locale" and @aria-pressed="true"]')->length);
            $this->assertSame(6, $xpath->query('//*[@aria-labelledby="guide-title"]/ol/li')->length);
            $this->assertSame(1, $xpath->query('//h1')->length);
            $response->assertSee(__('public.publication', [], $locale));
        }
    }
}
