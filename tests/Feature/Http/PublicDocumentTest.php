<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\Page;
use DOMDocument;
use DOMXPath;
use Tests\DatabaseTestCase;

final class PublicDocumentTest extends DatabaseTestCase
{
    public function testInformationPagesRenderCompleteDocuments(): void
    {
        $this->withoutVite();
        foreach (['changelog', 'license', 'impressum', 'datenschutz', 'tos', 'api-docs', 'game'] as $path) {
            $response = $this->get('/'.$path)->assertOk()->assertSee('<main', false);
            $document = new DOMDocument();
            @$document->loadHTML($response->getContent());
            $xpath = new DOMXPath($document);
            $this->assertSame(1, $xpath->query('//h1')->length, $path);
            $this->assertSame(1, $xpath->query('//meta[@name="description"]')->length, $path);
            $this->assertSame(url($path), $xpath->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        }
        $this->get('/license')->assertSee('GNU AFFERO GENERAL PUBLIC LICENSE');
        $this->get('/changelog')->assertSee('Unreleased');
        $this->get('/game')->assertSee('gameCanvas')->assertSee('Neu starten')->assertDontSee('fonts.googleapis.com');
    }

    public function testImprintContentDoesNotIntroduceAnotherPageHeading(): void
    {
        $this->withoutVite();
        Page::query()->updateOrCreate(['slug' => 'imprint'], ['title' => 'Imprint', 'content' => '# Anbieter']);
        $document = new DOMDocument();
        @$document->loadHTML($this->get('/impressum')->assertOk()->getContent());
        $this->assertSame(1, $document->getElementsByTagName('h1')->length);
    }

    public function testMissingPagePreservesStatusAndHidesUrlMetadata(): void
    {
        $this->withoutVite();
        config()->set('app.debug', false);
        $response = $this->get('/unknown-public-page?token=private')->assertNotFound()->assertSee('noindex');
        $response->assertDontSee('rel="canonical"', false)->assertDontSee('property="og:url"', false);
    }
}
