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
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
