<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', static function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'team_id')) {
                $table->foreignId('team_id')
                    ->after('id')
                    ->nullable()
                    ->constrained('teams')
                    ->nullOnDelete();
            } else {
                $table->unsignedBigInteger('team_id')->nullable()->change();
                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->nullOnDelete();
            }
        });

        Schema::table('model_has_roles', static function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_roles', 'team_id')) {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('role_id')
                    ->constrained('teams')
                    ->nullOnDelete();
            } else {
                $table->unsignedBigInteger('team_id')->nullable()->change();
                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->nullOnDelete();
            }
        });

        Schema::table('model_has_permissions', static function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_permissions', 'team_id')) {
                $table->foreignId('team_id')
                    ->nullable()
                    ->after('permission_id')
                    ->constrained('teams')
                    ->nullOnDelete();
            } else {
                $table->unsignedBigInteger('team_id')->nullable()->change();
                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropForeignKeysWithColumn('model_has_permissions', 'team_id');
        $this->dropForeignKeysWithColumn('model_has_roles', 'team_id');
        $this->dropForeignKeysWithColumn('roles', 'team_id');
    }

    private function dropForeignKeysWithColumn(string $table, string $column): void
    {
        $foreignKeys = Schema::getForeignKeys($table);

        foreach ($foreignKeys as $fk) {
            if (in_array($column, $fk['columns'], true)) {
                Schema::table($table, static function (Blueprint $table) use ($fk, $column) {
                    $table->dropForeign($fk['name']);
                    $table->dropColumn($column);
                });
            }
        }
    }
};
