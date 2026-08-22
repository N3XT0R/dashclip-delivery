<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeAction;
use N3XT0R\LaravelPassportAuthorizationCore\Models\PassportScopeResource;
use N3XT0R\LaravelPassportAuthorizationCore\Services\Scopes\ScopeRegistryService;

class PassportScopeTaxonomySeeder extends Seeder
{
    private const array RESOURCES = ['videos', 'channels', 'offers', 'teams'];

    private const array GLOBAL_ACTIONS = ['read', 'write', 'delete'];

    public function run(): void
    {
        foreach (self::RESOURCES as $resourceName) {
            PassportScopeResource::query()->firstOrCreate(
                ['name' => $resourceName],
                ['description' => ucfirst($resourceName) . ' REST API resource', 'is_active' => true],
            );
        }

        foreach (self::GLOBAL_ACTIONS as $actionName) {
            PassportScopeAction::query()->firstOrCreate(
                ['name' => $actionName],
                [
                    'description' => ucfirst($actionName) . ' access',
                    'resource_id' => null,
                    'is_active' => true,
                ],
            );
        }

        app(ScopeRegistryService::class)->clearCache();
    }
}
