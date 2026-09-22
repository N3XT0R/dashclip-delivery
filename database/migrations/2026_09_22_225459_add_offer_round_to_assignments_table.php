<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            // How often this channel has been offered this video; existing offers count as the first round.
            $table->unsignedSmallInteger('offer_round')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', static function (Blueprint $table): void {
            $table->dropColumn('offer_round');
        });
    }
};
