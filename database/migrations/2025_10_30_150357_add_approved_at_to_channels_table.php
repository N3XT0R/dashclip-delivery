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
        Schema::table('channels', static function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('is_video_reception_paused');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
