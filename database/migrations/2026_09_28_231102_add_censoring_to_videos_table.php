<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers whether a video is to be blurred and where its untouched original stays.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table): void {
            // null means nobody decided yet, which happens while the team of the video is unknown
            $table->boolean('censor_requested')->nullable()->after('processing_status');
            $table->string('source_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table): void {
            $table->dropColumn(['censor_requested', 'source_path']);
        });
    }
};
