<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Services\ApiDocumentationService;
use Tests\TestCase;

final class ApiDocsPageTest extends TestCase
{
    public function testPageRendersEveryDocumentationSetAndIsLinkedInTheFooter(): void
    {
        $response = $this->get(route('api-docs'));

        $response->assertOk()
            ->assertSee('Dashclip Delivery — Submitter API')
            ->assertSee('Dashclip Delivery — Channel Operator API')
            ->assertSee(route('l5-swagger.submitter.api'), false)
            ->assertSee(route('l5-swagger.submitter.docs'), false)
            ->assertSee(route('l5-swagger.channel-operator.api'), false)
            ->assertSee(route('api-docs'), false);
    }

    public function testServicePicksUpNewDocumentationSetsFromConfig(): void
    {
        config()->set('l5-swagger.documentations', [
            'submitter' => ['api' => ['title' => 'Submitter API']],
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
