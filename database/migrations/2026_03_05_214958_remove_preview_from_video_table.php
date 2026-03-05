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
        $this->cleanPreviews();
        Schema::table('videos', static function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }

    private function cleanPreviews(): void
    {
        try {
            Storage::disk(
                config('preview.default_disk', 'public')
            )->deleteDirectory('previews');
        } catch (\Throwable) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', static function (Blueprint $table) {
            $table->string('path')->nullable()->after('bytes');
        });
    }
};
