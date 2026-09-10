<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Services\ApiDocumentationService;
use Tests\TestCase;

final class ApiDocsPageTest extends TestCase
{
    public function testPageRendersEveryConfiguredDocumentationSetAndIsLinkedInTheFooter(): void
    {
        $response = $this->get(route('api-docs'));

        $response->assertOk()
            ->assertSee('Dashclip Delivery API')
            ->assertSee(route('l5-swagger.default.api'), false)
            ->assertSee(route('l5-swagger.default.docs'), false)
            ->assertSee(route('api-docs'), false);
    }

    public function testServicePicksUpNewDocumentationSetsFromConfig(): void
    {
        config()->set('l5-swagger.documentations', [
            'default' => ['api' => ['title' => 'Dashclip Delivery API']],
            'partner' => [
                'api' => ['title' => 'Partner API'],
                'routes' => ['api' => 'partner/documentation', 'docs' => 'partner/docs'],
            ],
        ]);

        $docs = app(ApiDocumentationService::class)->all();

        $this->assertCount(2, $docs);
        $this->assertSame('Partner API', $docs[1]->title);
        $this->assertSame(url('partner/documentation'), $docs[1]->uiUrl);
        $this->assertSame(url('partner/docs'), $docs[1]->specUrl);
    }
}
