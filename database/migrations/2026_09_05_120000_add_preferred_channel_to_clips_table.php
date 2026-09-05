<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clips', static function (Blueprint $table): void {
            // Raw value from info.csv, regardless of validity — for traceability/debugging.
            $table->string('preferred_channel')->nullable()->after('user_id');
            // Channel resolved at import time; only set when it exists AND is not paused.
            $table->foreignId('preferred_channel_id')
                ->nullable()
                ->after('preferred_channel')
                ->constrained('channels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clips', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('preferred_channel_id');
            $table->dropColumn('preferred_channel');
        });
    }
};
