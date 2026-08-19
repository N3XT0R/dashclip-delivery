<?php

declare(strict_types=1);

namespace App\Services;

class LocaleDiscoveryService
{
    private const LABELS = [
        'de' => 'Deutsch',
        'en' => 'English',
    ];

    /**
     * App-owned available locale codes: top-level directories in lang/, excluding "vendor".
     *
     * @return list<string>
     */
    public function list(): array
    {
        $locales = [];

        foreach (scandir(lang_path()) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'vendor') {
                continue;
            }

            if (is_dir(lang_path($entry))) {
                $locales[] = $entry;
            }
        }

        return $locales;
    }

    /**
     * Human-readable display name for a locale code; unknown codes fall back to the uppercase code.
     */
    public function label(string $locale): string
    {
        return self::LABELS[$locale] ?? strtoupper($locale);
    }
}
