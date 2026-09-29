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
        DB::table('configs')->where('config_category_id', $categoryId)
            ->whereIn('key', ['censor_columns', 'censor_rows', 'censor_frame_step',
                'censor_confidence', 'censor_margin', 'censor_timeout_seconds'])->delete();
        if (!DB::table('configs')->where('config_category_id', $categoryId)->exists()) {
            DB::table('config_categories')->where('id', $categoryId)->delete();
        }
    }
};
