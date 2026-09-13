<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostTag extends Model
{
    use HasFactory;

    protected $table = 'blog_tags';

    protected $fillable = ['slug'];

    public function translations(): HasMany
    {
        return $this->hasMany(PostTagTranslation::class, 'tag_id');
    }

    /**
     * Return the translation for one locale, or null when it does not exist.
     * @param string $locale
     * @return PostTagTranslation|null
     */
    public function translation(string $locale): ?PostTagTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }
}
