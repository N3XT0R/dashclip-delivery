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


            $table->json('processing_meta')
                ->nullable()
                ->after('processing_status');
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
                'processing_meta',
            ]);
        });
    }
};
