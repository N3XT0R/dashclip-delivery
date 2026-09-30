<?php

declare(strict_types=1);

use App\Constants\Config\CensorConfigEntry;
use Database\Seeders\CensorConfigSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds how many threads the detection may use, so a machine with more cores can be put to work
 * without a deployment.
 */
return new class () extends Migration {
    public function up(): void
    {
        app(CensorConfigSeeder::class)->run();
    }

    public function down(): void
    {
        DB::table('configs')->where('key', CensorConfigEntry::THREADS)->delete();
    }
};
