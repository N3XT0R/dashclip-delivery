<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use N3XT0R\FilamentPassportUi\Database\Factories\ClientFactory;
use Tests\DatabaseTestCase;

/**
 * Guards the column that binds a scope grant to the client it was issued for.
 *
 * Clients use uuid primary keys. When context_client_id is anything narrower
 * (it was a bigint before the repair migration), MySQL/MariaDB truncates the
 * uuid on write and aborts every OAuth client creation. SQLite does not enforce
 * column types, so the only portable way to catch that here is to compare the
 * column against the primary key it references.
 */
final class PassportScopeGrantSchemaTest extends DatabaseTestCase
{
    public function testContextClientIdMatchesTheClientPrimaryKeyType(): void
    {
        $this->assertSame(
            Schema::getColumnType('oauth_clients', 'id'),
            Schema::getColumnType('passport_scope_grants', 'context_client_id'),
            'context_client_id must have the same column type as oauth_clients.id, '
            . 'otherwise MySQL/MariaDB truncates the client uuid on write.',
        );
    }

    public function testAClientUuidSurvivesARoundTripThroughContextClientId(): void
    {
        $client = ClientFactory::new()->create();
        $resourceId = DB::table('passport_scope_resources')->value('id');
        $actionId = DB::table('passport_scope_actions')->value('id');

        DB::table('passport_scope_grants')->insert([
            'tokenable_type' => $client->getMorphClass(),
            'tokenable_id' => $client->getKey(),
            'context_client_id' => $client->getKey(),
            'resource_id' => $resourceId,
            'action_id' => $actionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            (string)$client->getKey(),
            (string)DB::table('passport_scope_grants')->value('context_client_id'),
        );
    }
}
