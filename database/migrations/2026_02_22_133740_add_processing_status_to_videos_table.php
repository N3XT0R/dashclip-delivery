<?php

use App\Models\Video;
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
            $table->string('processing_status')
                ->default('pending')
                ->index()
                ->after('path');

            $table->unsignedTinyInteger('processing_progress')
                ->default(0)
                ->after('processing_status');

            $table->text('processing_error_message')
                ->nullable()
                ->after('processing_progress');
        });

        Video::query()->update(['processing_status' => 'completed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', static function (Blueprint $table) {
            $table->dropColumn([
                'processing_status',
                'processing_progress',
                'processing_error_message',
            ]);
        });
    }
};
