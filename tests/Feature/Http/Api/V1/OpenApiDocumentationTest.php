<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Api\V1;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

final class OpenApiDocumentationTest extends DatabaseTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>, 3: list<string>}>
     */
    public static function documentations(): array
    {
        return [
            'submitter' => [
                'submitter',
                'submitter-api-docs.json',
                ['/api/v1/videos', '/api/v1/videos/{video}', '/api/v1/teams', '/api/v1/teams/{team}'],
                ['videos:read', 'teams:read'],
            ],
            'channel-operator' => [
                'channel-operator',
                'channel-operator-api-docs.json',
                [
                    '/api/v1/channels',
                    '/api/v1/channels/{channel}',
                    '/api/v1/offers',
                    '/api/v1/offers/{offer}',
                    '/api/v1/offers/{offer}/comment',
                ],
                ['channels:read', 'offers:read'],
            ],
        ];
    }

    /**
     * @param  list<string>  $expectedPaths
     * @param  list<string>  $expectedScopes
     */
    #[DataProvider('documentations')]
    public function testDocumentationGeneratesWithExpectedPathsScopesAndDescriptions(
        string $documentation,
        string $file,
        array $expectedPaths,
        array $expectedScopes,
    ): void {
        $this->assertSame(0, Artisan::call('l5-swagger:generate', ['documentation' => $documentation]));

        $spec = json_decode(
            (string)file_get_contents(storage_path('api-docs/' . $file)),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('3.0.0', $spec['openapi']);
        $this->assertSame('/', $spec['servers'][0]['url']);
        $this->assertEqualsCanonicalizing($expectedPaths, array_keys($spec['paths']));

        $schemes = $spec['components']['securitySchemes'];
        $this->assertSame('oauth2', $schemes['passport']['type']);
        $this->assertSame(['authorizationCode'], array_keys($schemes['passport']['flows']));
        $this->assertSame('http', $schemes['bearerAuth']['type']);
        foreach ($expectedScopes as $scope) {
            $this->assertArrayHasKey(
                $scope,
                $schemes['passport']['flows']['authorizationCode']['scopes'],
            );
        }

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

    /**
     * @return array<string, array{0: string}>
     */
    public static function documentationKeys(): array
    {
        return ['submitter' => ['submitter'], 'channel-operator' => ['channel-operator']];
    }

    #[DataProvider('documentationKeys')]
    public function testSwaggerUiIsReachable(string $documentation): void
    {
        Artisan::call('l5-swagger:generate', ['documentation' => $documentation]);

        $this->get(route("l5-swagger.{$documentation}.api"))->assertOk();
    }
}
