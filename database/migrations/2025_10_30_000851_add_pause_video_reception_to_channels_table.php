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
            $table->boolean('is_video_reception_paused')
                ->default(false)
                ->after('weekly_quota')
                ->comment('Temporarily pause receiving videos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->dropColumn('is_video_reception_paused');
        });
    }
};
