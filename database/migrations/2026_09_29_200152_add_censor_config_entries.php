<?php

declare(strict_types=1);

use Database\Seeders\CensorConfigSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /** Create the editable category and import existing installation values once. */
    public function up(): void
    {
        app(CensorConfigSeeder::class)->run();
    }

    /** Remove only this category and its processing settings. */
    public function down(): void
    {
        $categoryId = DB::table('config_categories')->where('slug', 'censor')->value('id');
        if ($categoryId === null) {
            return;
        }
        // everything this category holds belongs to the blurring, later keys included
        DB::table('configs')->where('config_category_id', $categoryId)->delete();
        DB::table('config_categories')->where('id', $categoryId)->delete();
    }
};
