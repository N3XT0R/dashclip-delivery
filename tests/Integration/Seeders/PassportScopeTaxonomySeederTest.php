<?php

declare(strict_types=1);

namespace Tests\Integration\Seeders;

use Database\Seeders\PassportScopeTaxonomySeeder;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeAction;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeResource;
use N3XT0R\LaravelPassportAuthorizationCore\Services\Scopes\ScopeRegistryService;
use Tests\DatabaseTestCase;

final class PassportScopeTaxonomySeederTest extends DatabaseTestCase
{
    public function testSeederCreatesFullTaxonomy(): void
    {
        $this->seed(PassportScopeTaxonomySeeder::class);

        $scopes = app(ScopeRegistryService::class)->allScopeNames()->pluck('scope');
        $expected = [
            'videos:read', 'videos:write', 'videos:delete',
            'channels:read', 'channels:write', 'channels:delete',
            'offers:read', 'offers:write', 'offers:delete', 'offers:download',
            'teams:read', 'teams:write', 'teams:delete',
        ];
        foreach ($expected as $scope) {
            $this->assertContains($scope, $scopes->all(), "missing scope {$scope}");
        }
        $this->assertNotContains('videos:download', $scopes->all());
        $this->assertNotContains('channels:download', $scopes->all());
    }

    public function testSeederIsIdempotent(): void
    {
        $this->seed(PassportScopeTaxonomySeeder::class);
        $this->seed(PassportScopeTaxonomySeeder::class);

        $this->assertSame(4, PassportScopeResource::query()->count());
        $this->assertSame(4, PassportScopeAction::query()->count());
    }
}
