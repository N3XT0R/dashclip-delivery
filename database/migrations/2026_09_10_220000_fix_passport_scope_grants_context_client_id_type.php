<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair databases that ran the pre-1.3.1 version of
 * add_client_id_to_passport_scope_grants_table.
 *
 * That version created context_client_id with foreignId(), so the column is a
 * bigint while oauth_clients.id is a uuid. The source migration was corrected
 * to foreignUuid() in laravel-passport-authorization-core 1.3.1, but a
 * migration that already ran never runs again, so existing databases keep the
 * bigint column. Writing a client uuid into it then fails on MySQL/MariaDB
 * with "1265 Data truncated for column 'context_client_id'", which aborts every
 * OAuth client creation before the generated secret can be shown.
 *
 * SQLite does not enforce column types, so fresh test databases already have
 * the correct type and this migration is a no-op there.
 */
return new class () extends Migration {
    private const string TABLE = 'passport_scope_grants';

    private const string COLUMN = 'context_client_id';

    private const string UNIQUE_INDEX = 'passport_scope_grant_unique';

    private const string COLUMN_INDEX = 'passport_scope_grants_context_client_idx';

    /**
     * @var list<string>
     */
    private const array UNIQUE_COLUMNS = [
        'tokenable_type',
        'tokenable_id',
        'resource_id',
        'action_id',
        self::COLUMN,
    ];

    public function up(): void
    {
        if (!$this->needsRepair()) {
            return;
        }

        $this->dropIndexes();

        // The column is recreated rather than altered: MariaDB refuses to cast
        // a bigint to its native uuid type, and any value the bigint column
        // holds was truncated on write and can no longer identify a client.
        // The grant rows themselves survive, the column is nullable.
        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->dropColumn(self::COLUMN);
        });

        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->foreignUuid(self::COLUMN)
                ->nullable()
                ->after('tokenable_type')
                ->constrained('oauth_clients')
                ->cascadeOnDelete();
        });

        $this->createIndexes();
    }

    public function down(): void
    {
        // Intentionally irreversible: reverting to bigint would recreate the
        // defect this migration exists to repair.
    }

    private function needsRepair(): bool
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return false;
        }

        return !in_array(
            Schema::getColumnType(self::TABLE, self::COLUMN),
            ['char', 'varchar', 'string', 'uuid', 'guid'],
            true,
        );
    }

    private function dropIndexes(): void
    {
        foreach ($this->indexNames() as $name) {
            Schema::table(self::TABLE, static function (Blueprint $table) use ($name): void {
                $table->dropIndex($name);
            });
        }

        foreach ($this->foreignKeyNames() as $name) {
            Schema::table(self::TABLE, static function (Blueprint $table) use ($name): void {
                $table->dropForeign($name);
            });
        }
    }

    private function createIndexes(): void
    {
        Schema::table(self::TABLE, static function (Blueprint $table): void {
            $table->index([self::COLUMN], self::COLUMN_INDEX);
            $table->unique(self::UNIQUE_COLUMNS, self::UNIQUE_INDEX);
        });
    }

    /**
     * @return list<string>
     */
    private function indexNames(): array
    {
        $wanted = [self::UNIQUE_INDEX, self::COLUMN_INDEX];

        return array_values(array_filter(
            array_map(static fn (array $index): string => (string)$index['name'], Schema::getIndexes(self::TABLE)),
            static fn (string $name): bool => in_array($name, $wanted, true),
        ));
    }

    /**
     * @return list<string>
     */
    private function foreignKeyNames(): array
    {
        return array_values(array_map(
            static fn (array $key): string => (string)$key['name'],
            array_filter(
                Schema::getForeignKeys(self::TABLE),
                static fn (array $key): bool => in_array(self::COLUMN, $key['columns'], true),
            ),
        ));
    }
};
