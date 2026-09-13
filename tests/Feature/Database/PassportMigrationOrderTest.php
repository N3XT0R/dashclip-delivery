<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PassportMigrationOrderTest extends TestCase
{
    private const string ADD_COLUMN = '2026_08_20_211553_add_client_id_to_passport_scope_grants_table.php';
    private const string ADD_FOREIGN = '2026_09_13_134846_add_context_client_foreign_key_after_oauth_clients_creation.php';

    private bool $mayCleanUp = false;

    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.passport_migration_test' => config('database.connections.'.$this->originalConnection)]);
        DB::setDefaultConnection('passport_migration_test');
        Schema::clearResolvedInstance('db.schema');

        $this->assertTrue(
            DB::connection()->getDatabaseName() === ':memory:'
            || str_ends_with(DB::connection()->getDatabaseName(), '_migration_test'),
            'Migration tests require an isolated in-memory or dedicated _migration_test database.',
        );
        $this->mayCleanUp = true;

        foreach ([
            '2026_08_20_211550_create_passport_scope_resources_table.php',
            '2026_08_20_211551_create_passport_scope_actions_table.php',
            '2026_08_20_211552_create_passport_scope_grant_table.php',
        ] as $filename) {
            (require database_path('migrations/'.$filename))->up();
        }
    }

    protected function tearDown(): void
    {
        if ($this->mayCleanUp) {
            Schema::dropIfExists('passport_scope_grants');
            Schema::dropIfExists('passport_scope_actions');
            Schema::dropIfExists('passport_scope_resources');
            Schema::dropIfExists('oauth_clients');
        }
        DB::purge('passport_migration_test');
        DB::setDefaultConnection($this->originalConnection);
        Schema::clearResolvedInstance('db.schema');
        parent::tearDown();
    }

    public function testClientReferenceIsDeferredUntilItsParentExists(): void
    {
        $this->assertFalse(Schema::hasTable('oauth_clients'));
        (require database_path('migrations/'.self::ADD_COLUMN))->up();
        $this->assertSame([], array_values(array_filter(
            Schema::getForeignKeys('passport_scope_grants'),
            static fn (array $key): bool => $key['columns'] === ['context_client_id'],
        )));
        $this->completePassportSetup();
    }

    public function testRetryPreservesTheColumnAndExistingGrantsAfterForeignKeyFailure(): void
    {
        Schema::table('passport_scope_grants', static function (Blueprint $table): void {
            $table->uuid('context_client_id')->nullable();
        });
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $failed = false;
            try {
                Schema::table('passport_scope_grants', static function (Blueprint $table): void {
                    $table->foreign('context_client_id')->references('id')->on('oauth_clients')->cascadeOnDelete();
                });
            } catch (QueryException $exception) {
                $failed = true;
                $this->assertContains($exception->errorInfo[1], [1005, 1824]);
            }
            $this->assertTrue($failed, 'The old migration must reproduce the missing-parent foreign-key failure.');
        }
        DB::table('passport_scope_resources')->insert(['id' => 1, 'name' => 'videos']);
        DB::table('passport_scope_actions')->insert(['id' => 1, 'resource_id' => 1, 'name' => 'read']);
        DB::table('passport_scope_grants')->insert([
            'id' => 1, 'tokenable_type' => 'user', 'tokenable_id' => '42',
            'resource_id' => 1, 'action_id' => 1,
        ]);

        $migration = require database_path('migrations/'.self::ADD_COLUMN);
        $migration->up();
        $migration->up();
        $this->completePassportSetup();
        $this->assertSame('42', DB::table('passport_scope_grants')->where('id', 1)->value('tokenable_id'));
    }

    private function completePassportSetup(): void
    {
        (require database_path('migrations/2026_08_20_211554_change_passport_scope_grant_unique_index.php'))->up();
        (require database_path('migrations/2026_08_20_212140_create_oauth_clients_table.php'))->up();
        $migration = require database_path('migrations/'.self::ADD_FOREIGN);
        $migration->up();
        $migration->up();

        $keys = array_values(array_filter(
            Schema::getForeignKeys('passport_scope_grants'),
            static fn (array $key): bool => $key['columns'] === ['context_client_id'],
        ));
        $this->assertCount(1, $keys);
        $this->assertSame('oauth_clients', $keys[0]['foreign_table']);
        $this->assertSame('cascade', strtolower($keys[0]['on_delete']));
        $this->assertTrue(Schema::hasIndex('passport_scope_grants', 'passport_scope_grants_context_client_idx'));
        DB::table('passport_scope_resources')->insertOrIgnore(['id' => 1, 'name' => 'videos']);
        DB::table('passport_scope_actions')->insertOrIgnore(['id' => 1, 'resource_id' => 1, 'name' => 'read']);
        $clientId = '12345678-1234-4234-8234-123456789012';
        DB::table('oauth_clients')->insert([
            'id' => $clientId, 'name' => 'Migration test', 'redirect_uris' => '[]',
            'grant_types' => '[]', 'revoked' => false,
        ]);
        DB::table('passport_scope_grants')->insert([
            'id' => 2, 'tokenable_type' => 'client', 'tokenable_id' => $clientId,
            'resource_id' => 1, 'action_id' => 1, 'context_client_id' => $clientId,
        ]);
        DB::table('oauth_clients')->where('id', $clientId)->delete();
        $this->assertFalse(DB::table('passport_scope_grants')->where('id', 2)->exists());

        $migration->down();
        $migration->up();
    }
}
