<?php

use App\Constants\Config\DefaultConfigEntry;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $id = DB::table('config_categories')->where('slug', 'default')->value('id');
        DB::table('configs')
            ->insert([
                'key' => DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS,
                'value' => 1,
                'cast_type' => 'int',
                'config_category_id' => $id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'is_visible' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configs')
            ->where('key', DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS)
            ->delete();
    }
};
