<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Add the client reference after Passport has created its client table.
     * Already migrated installations retain their existing foreign key.
     */
    public function up(): void
    {
        foreach (Schema::getForeignKeys('passport_scope_grants') as $foreignKey) {
            if ($foreignKey['columns'] === ['context_client_id']) {
                return;
            }
        }

        Schema::table('passport_scope_grants', static function (Blueprint $table): void {
            $table->foreign('context_client_id')->references('id')->on('oauth_clients')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('passport_scope_grants', static function (Blueprint $table): void {
            $table->dropForeign(['context_client_id']);
        });
    }
};
