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
        Schema::table('videos', static function (Blueprint $table) {
            $table->dropUnique(['hash']);
            $table->unique(['hash', 'disk', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', static function (Blueprint $table) {
            $table->string('hash', 64)->unique()->change();
        });
    }
};
