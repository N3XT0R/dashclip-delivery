<?php

declare(strict_types=1);

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class BlogCacheObserver
{
    /** Invalidate public aggregates after editorial changes. */
    public function saved(Model $model): void
    {
        foreach (['de', 'en'] as $locale) {
            Cache::forget('blog.homepage.'.$locale);
            Cache::forget('blog.category_counts.'.$locale);
        }
    }

    /** Invalidate the same aggregates when content is removed. */
    public function deleted(Model $model): void
    {
        $this->saved($model);
    }
}
