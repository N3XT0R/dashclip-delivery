<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('youtube_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
