<?php

declare(strict_types=1);

use App\Constants\Config\DefaultConfigEntry;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $categoryId = DB::table('config_categories')->where('slug', 'default')->value('id');

        DB::table('configs')->insert([
            'key' => DefaultConfigEntry::DISTRIBUTION_ROUNDS,
            'value' => 1,
            'cast_type' => 'int',
            'config_category_id' => $categoryId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'is_visible' => 1,
        ]);
    }

    public function down(): void
    {
        DB::table('configs')
            ->where('key', DefaultConfigEntry::DISTRIBUTION_ROUNDS)
            ->delete();
    }
};
