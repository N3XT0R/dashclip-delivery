<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            $table->boolean('via_preferred_channel')->default(false)->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            $table->dropColumn('via_preferred_channel');
        });
    }
};
