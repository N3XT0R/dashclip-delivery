<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Prepare the client column without referencing the not-yet-created OAuth table.
     * Keep a column left behind by MySQL's non-transactional failed ALTER TABLE.
     * The later add_context_client_foreign_key_after_oauth_clients_creation migration
     * installs the constraint for both new and already migrated databases.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('passport_scope_grants', 'context_client_id')) {
            Schema::table('passport_scope_grants', static function (Blueprint $table): void {
                $table->uuid('context_client_id')->nullable()->after('tokenable_type');
            });
        }

        if (!Schema::hasIndex('passport_scope_grants', 'passport_scope_grants_context_client_idx')) {
            Schema::table('passport_scope_grants', static function (Blueprint $table): void {
                $table->index(['context_client_id'], 'passport_scope_grants_context_client_idx');
            });
        }
    }

    public function down(): void
    {
        foreach (Schema::getForeignKeys('passport_scope_grants') as $foreignKey) {
            if ($foreignKey['columns'] === ['context_client_id']) {
                Schema::table('passport_scope_grants', static function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });
            }
        }

        Schema::table('passport_scope_grants', static function (Blueprint $table): void {
            $table->dropIndex('passport_scope_grants_context_client_idx');
            $table->dropColumn('context_client_id');
        });
    }
};
