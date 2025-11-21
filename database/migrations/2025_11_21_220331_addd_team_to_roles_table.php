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
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', static function (Blueprint $table) {
            if (Schema::hasIndex('roles', 'roles_team_id_foreign')) {
                $table->dropForeign('roles_team_id_foreign');
            }
            $table->dropColumn('team_id');
        });
    }
};
