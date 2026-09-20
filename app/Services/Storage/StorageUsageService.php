<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Constants\Config\DefaultConfigEntry;
use App\Facades\Cfg;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;

/**
 * Tells public pages which storage the platform currently relies on.
 */
final readonly class StorageUsageService
{
    private const CACHE_KEY = 'storage.usage.dropbox';

    private const CACHE_SECONDS = 300;

    /** True while videos are stored on Dropbox or new uploads still go there; cached briefly for public pages. */
    public function usesDropbox(): bool
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, static function (): bool {
            if ((string) Cfg::get(DefaultConfigEntry::DEFAULT_FILE_SYSTEM, 'default', 'local') === 'dropbox') {
                return true;
            }

            return Video::query()->where('disk', 'dropbox')->exists();
        });
    }
}
