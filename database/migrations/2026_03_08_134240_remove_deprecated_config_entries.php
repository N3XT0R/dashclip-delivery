<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('configs')
            ->whereIn('key', [
                'email_reminder',
            ])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $categoryId = DB::table('config_categories')->where('slug', 'email')->value('id');
        DB::table('configs')->insert([
            'key' => 'email_reminder',
            'value' => 0,
            'cast_type' => 'bool',
            'config_category_id' => $categoryId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'is_visible' => 1,
        ]);
    }
};
