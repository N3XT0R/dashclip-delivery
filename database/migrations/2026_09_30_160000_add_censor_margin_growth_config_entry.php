<?php

declare(strict_types=1);

use App\Constants\Config\CensorConfigEntry;
use Database\Seeders\CensorConfigSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds how far a found region grows while it is carried over frames without a fresh detection.
 */
return new class () extends Migration {
    public function up(): void
    {
        app(CensorConfigSeeder::class)->run();
    }

    public function down(): void
    {
        DB::table('configs')->where('key', CensorConfigEntry::MARGIN_GROWTH)->delete();
    }
};
