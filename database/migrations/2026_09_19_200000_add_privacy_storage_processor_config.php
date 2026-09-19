<?php

declare(strict_types=1);

use App\Constants\Config\PrivacyConfigEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Add the storage processor name and the link to its data processing agreement, both empty until set.
     */
    public function up(): void
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $defaultId = DB::table('config_categories')->where('slug', 'default')->value('id');

        foreach ([PrivacyConfigEntry::STORAGE_PROVIDER_NAME, PrivacyConfigEntry::STORAGE_DPA_URL] as $key) {
            DB::table('configs')->insertOrIgnore([
                'key' => $key,
                'value' => '',
                'cast_type' => 'string',
                'is_visible' => 1,
                'config_category_id' => $defaultId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('configs')
            ->whereIn('key', [PrivacyConfigEntry::STORAGE_PROVIDER_NAME, PrivacyConfigEntry::STORAGE_DPA_URL])
            ->delete();
    }
};
