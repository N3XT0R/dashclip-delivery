<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            // Never written since the table was created; the offer round replaced it.
            $table->dropColumn('attempts');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            $table->unsignedSmallInteger('attempts')->default(0)->after('offer_round');
        });
    }
};
