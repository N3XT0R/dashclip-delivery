<?php

declare(strict_types=1);

use App\Constants\Config\CensorConfigEntry;
use Database\Seeders\CensorConfigSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds where in the picture the search for number plates begins, so the sky is no longer searched.
 */
return new class () extends Migration {
    public function up(): void
    {
        app(CensorConfigSeeder::class)->run();
    }

    public function down(): void
    {
        DB::table('configs')->where('key', CensorConfigEntry::SEARCH_FROM)->delete();
    }
};
