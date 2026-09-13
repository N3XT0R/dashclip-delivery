<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Blog\PostStatusEnum;
use App\Services\Blog\ReadingTimeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTranslation extends Model
{
    use HasFactory;

    protected $table = 'blog_post_translations';

    protected $fillable = [
        'post_id', 'locale', 'slug', 'title', 'excerpt', 'content',
        'meta_title', 'meta_description', 'canonical_url', 'is_indexable',
        'status', 'published_at',
    ];

    protected $casts = [
        'status' => PostStatusEnum::class,
        'published_at' => 'datetime',
        'is_indexable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $translation): void {
            $translation->reading_minutes = app(ReadingTimeService::class)
                ->minutesFor((string)$translation->content);
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Restrict the query to translations a reader may see right now.
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatusEnum::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
