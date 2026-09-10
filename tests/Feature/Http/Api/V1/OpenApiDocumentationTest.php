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
    public static function resourceDocumentations(): array
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
     * @return array<string, array{0: string}>
     */
    public static function allDocumentations(): array
    {
        return [
            'authentication' => ['authentication'],
            'submitter' => ['submitter'],
            'channel-operator' => ['channel-operator'],
        ];
    }

    /**
     * @param  list<string>  $expectedPaths
     * @param  list<string>  $expectedScopes
     */
    #[DataProvider('resourceDocumentations')]
    public function testResourceDocumentationGeneratesWithExpectedPathsAndScopes(
        string $documentation,
        string $file,
        array $expectedPaths,
        array $expectedScopes,
    ): void {
        $spec = $this->generate($documentation, $file);

        $this->assertSame('/', $spec['servers'][0]['url']);
        $this->assertEqualsCanonicalizing($expectedPaths, array_keys($spec['paths']));

        $schemes = $spec['components']['securitySchemes'];
        $this->assertSame('oauth2', $schemes['oauth2']['type']);
        $this->assertSame(['authorizationCode'], array_keys($schemes['oauth2']['flows']));
        $this->assertSame('http', $schemes['bearerAuth']['type']);
        foreach ($expectedScopes as $scope) {
            $this->assertArrayHasKey(
                $scope,
                $schemes['oauth2']['flows']['authorizationCode']['scopes'],
            );
        }
    }

    public function testGeneratedSpecsDoNotLeakInternalFrameworkNames(): void
    {
        foreach (['authentication', 'submitter', 'channel-operator'] as $documentation) {
            Artisan::call('l5-swagger:generate', ['documentation' => $documentation]);
            $file = config("l5-swagger.documentations.{$documentation}.paths.docs_json");
            $json = strtolower((string)file_get_contents(storage_path('api-docs/' . $file)));

            $this->assertStringNotContainsString('passport', $json, "{$documentation} spec leaks a framework name");
            $this->assertStringNotContainsString('laravel', $json, "{$documentation} spec leaks a framework name");
        }
    }

    public function testAuthenticationDocumentationCoversTheOAuthEndpoints(): void
    {
        $spec = $this->generate('authentication', 'authentication-api-docs.json');

        $this->assertEqualsCanonicalizing(
            [
                '/oauth/token',
                '/oauth/token/refresh',
                '/oauth/authorize',
                '/oauth/device/code',
                '/oauth/device',
                '/oauth/device/authorize',
            ],
            array_keys($spec['paths']),
        );
    }

    /**
     * Every documented operation across every documentation must carry a
     * behavioural description (ADR 0008).
     */
    #[DataProvider('allDocumentations')]
    public function testEveryOperationHasADescription(string $documentation): void
    {
        $file = config("l5-swagger.documentations.{$documentation}.paths.docs_json");
        $spec = $this->generate($documentation, $file);

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

    #[DataProvider('allDocumentations')]
    public function testSwaggerUiIsReachable(string $documentation): void
    {
        Artisan::call('l5-swagger:generate', ['documentation' => $documentation]);

        $this->get(route("l5-swagger.{$documentation}.api"))->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function generate(string $documentation, string $file): array
    {
        $this->assertSame(0, Artisan::call('l5-swagger:generate', ['documentation' => $documentation]));

        $spec = json_decode(
            (string)file_get_contents(storage_path('api-docs/' . $file)),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('3.0.0', $spec['openapi']);

        return $spec;
    }
}
