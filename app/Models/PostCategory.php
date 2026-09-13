<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use HasFactory;

    protected $table = 'blog_categories';

    protected $fillable = ['slug', 'icon'];

    public function translations(): HasMany
    {
        return $this->hasMany(PostCategoryTranslation::class, 'category_id');
    }

    /**
     * Return the translation for one locale, or null when it does not exist.
     * @param string $locale
     * @return PostCategoryTranslation|null
     */
    public function translation(string $locale): ?PostCategoryTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }
}
