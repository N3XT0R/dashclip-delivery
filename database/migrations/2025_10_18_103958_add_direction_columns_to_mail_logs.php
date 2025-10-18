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
        Schema::table('mail_logs', static function (Blueprint $table) {
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_logs', static function (Blueprint $table) {
            $table->dropColumn('direction');
        });
    }
};
