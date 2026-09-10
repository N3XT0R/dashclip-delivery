<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use Illuminate\Support\Facades\Artisan;
use Tests\DatabaseTestCase;

final class OpenApiDocumentationTest extends DatabaseTestCase
{
    public function testOpenApiSpecGeneratesWithoutErrors(): void
    {
        $this->assertSame(0, Artisan::call('l5-swagger:generate'));

        $spec = json_decode(
            (string)file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame('3.0.0', $spec['openapi']);
        $this->assertSame('/', $spec['servers'][0]['url']);
        foreach (['/api/v1/videos', '/api/v1/channels', '/api/v1/offers', '/api/v1/teams'] as $path) {
            $this->assertArrayHasKey($path, $spec['paths'], "missing path {$path}");
        }
        $this->assertArrayHasKey('Video', $spec['components']['schemas']);
        $this->assertArrayHasKey('PaginationMeta', $spec['components']['schemas']);

        $schemes = $spec['components']['securitySchemes'];
        $this->assertSame('oauth2', $schemes['passport']['type']);
        $this->assertSame(['authorizationCode'], array_keys($schemes['passport']['flows']));
        $this->assertArrayHasKey(
            'videos:read',
            $schemes['passport']['flows']['authorizationCode']['scopes'],
        );
        $this->assertSame('http', $schemes['bearerAuth']['type']);
        $this->assertSame('bearer', $schemes['bearerAuth']['scheme']);
    }

    public function testEveryDocumentedOperationHasADescription(): void
    {
        Artisan::call('l5-swagger:generate');

        $spec = json_decode(
            (string)file_get_contents(storage_path('api-docs/api-docs.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $missing = [];
        foreach ($spec['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (trim((string)($operation['description'] ?? '')) === '') {
                    $missing[] = strtoupper($method) . ' ' . $path;
                }
            }
        }

        $this->assertSame([], $missing, 'Operations without a description (ADR 0008): '
            . implode(', ', $missing));
    }

    public function testSwaggerUiIsReachable(): void
    {
        Artisan::call('l5-swagger:generate');
        $this->get('/api/documentation')->assertOk();
    }
}
