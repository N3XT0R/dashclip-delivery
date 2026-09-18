<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Exceptions\Blog\BlogSeedCoverMissingException;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Shared steps for seeders that ship editorial blog content.
 */
trait SeedsBlogContent
{
    /**
     * Resolve the first web administrator, who authors seeded articles.
     */
    private function blogAuthor(): ?User
    {
        return User::query()->whereHas('roles', static fn (Builder $query) => $query
            ->where('name', 'super_admin')->where('guard_name', 'web'))->orderBy('id')->first();
    }

    /**
     * Reuse existing category slugs while preserving their editorial labels.
     * @param array<string, array{icon: string, translations: array<string, array{name: string, slug: string, description: string}>}> $categories
     * @return array<string, int> Category identifiers keyed by the initial slug.
     */
    private function seedCategories(array $categories): array
    {
        $ids = [];
        foreach ($categories as $slug => $data) {
            $category = PostCategory::query()->firstOrCreate(['slug' => $slug], ['icon' => $data['icon']]);
            foreach ($data['translations'] as $locale => $translation) {
                $category->translations()->firstOrCreate(['locale' => $locale], $translation);
            }
            $ids[$slug] = $category->id;
        }
        return $ids;
    }

    /**
     * Reuse existing tag slugs and create only missing translations.
     * @param array<string, array<string, array{name: string, slug: string}>> $tags
     * @return array<string, int> Tag identifiers keyed by the initial slug.
     */
    private function seedTags(array $tags): array
    {
        $ids = [];
        foreach ($tags as $slug => $translations) {
            $tag = PostTag::query()->firstOrCreate(['slug' => $slug]);
            foreach ($translations as $locale => $translation) {
                $tag->translations()->firstOrCreate(['locale' => $locale], $translation);
            }
            $ids[$slug] = $tag->id;
        }
        return $ids;
    }

    /**
     * Copy the shipped cover to the public disk, leaving a replaced image untouched.
     * The copy happens inside the surrounding transaction; a rollback leaves the image
     * in place, where the next run reuses it instead of writing it again.
     * @param string $article
     * @return string Path of the cover relative to the public disk.
     * @throws BlogSeedCoverMissingException When the shipped cover artwork is not readable.
     */
    private function storeCover(string $article): string
    {
        $path = 'blog/'.$article.'.webp';
        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $source = database_path('seeders/data/images/covers/'.$article.'.webp');
        $artwork = is_readable($source) ? file_get_contents($source) : false;
        if ($artwork === false) {
            throw BlogSeedCoverMissingException::forArticle($article, $source);
        }
        Storage::disk('public')->put($path, $artwork);

        return $path;
    }
}
