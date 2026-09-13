<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTagTranslation extends Model
{
    use HasFactory;

    protected $table = 'blog_tag_translations';

    protected $fillable = ['tag_id', 'locale', 'slug', 'name'];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(PostTag::class, 'tag_id');
    }
}
