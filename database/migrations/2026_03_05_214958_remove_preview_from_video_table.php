<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Storage::disk('public')->deleteDirectory('videos');
        } catch (\Throwable) {
        }
        Schema::table('video', static function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video', static function (Blueprint $table) {
            $table->string('path')->nullable()->after('bytes');
        });
    }
};
