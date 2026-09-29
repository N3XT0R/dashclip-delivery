<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Constants\Config\CensorConfigEntry;
use App\Models\Config;
use App\Models\Config\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CensorConfigSeeder extends Seeder
{
    /**
     * Create missing processing settings, importing installation values without overwriting edits.
     * @throws ValidationException when an imported value is outside the supported range
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $category = Category::query()->firstOrCreate(
                ['slug' => CensorConfigEntry::CATEGORY],
                ['name' => 'Kennzeichen-Verpixelung', 'is_visible' => true],
            );
            Config::query()->firstOrCreate(['key' => CensorConfigEntry::ENABLED], [
                // off until someone turns it on: it keeps a machine busy for minutes per video
                'value' => false,
                'cast_type' => 'bool',
                'is_visible' => true,
                'config_category_id' => $category->getKey(),
            ]);

            foreach (['columns' => 3, 'rows' => 2, 'frame_step' => 3, 'confidence' => 0.15,
                'margin' => 0.25, 'timeout_seconds' => 3600] as $name => $default) {
                $legacyKey = match ($name) {
                    'columns' => 'tiles.columns',
                    'rows' => 'tiles.rows',
                    default => $name,
                };
                Config::query()->firstOrCreate(['key' => 'censor_' . $name], [
                    'value' => config('censor.initial_settings.' . $name, config('censor.' . $legacyKey, $default)),
                    'cast_type' => is_int($default) ? 'int' : 'float',
                    'is_visible' => true,
                    'config_category_id' => $category->getKey(),
                ]);
            }
        });
    }
}
