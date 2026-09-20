<?php

declare(strict_types=1);

use App\Constants\Config\PrivacyConfigEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Add the name of the storage provider named in the privacy policy, empty until it is set.
     */
    public function up(): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');

        DB::table('configs')->insertOrIgnore([
            'key' => PrivacyConfigEntry::STORAGE_PROVIDER_NAME,
            'value' => '',
            'cast_type' => 'string',
            'is_visible' => 1,
            'config_category_id' => DB::table('config_categories')->where('slug', 'default')->value('id'),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    public function down(): void
    {
        DB::table('configs')->where('key', PrivacyConfigEntry::STORAGE_PROVIDER_NAME)->delete();
    }
};
