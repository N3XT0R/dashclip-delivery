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
        Schema::table('clip', static function (Blueprint $table) {
            $table->string('preview_disk')->nullable()->after('video_id');
            $table->string('preview_path')->nullable()->after('preview_disk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clip', static function (Blueprint $table) {
            $table->dropColumn('preview_path');
            $table->dropColumn('preview_disk');
        });
    }
};
