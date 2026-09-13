<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enum\Blog\PostStatusEnum;
use App\Exceptions\Blog\BlogSeedAuthorMissingException;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogContentSeeder extends Seeder
{
    private const SEED_NAME = 'initial-editorial-content';

    /**
     * Publish initial content once, committing the marker and content together.
     * Later runs preserve editorial changes, renamed slugs and deleted records.
     * @throws BlogSeedAuthorMissingException When no existing web administrator can author the article.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            if (DB::table('blog_seed_runs')->insertOrIgnore(['name' => self::SEED_NAME, 'completed_at' => now()]) === 0) {
                $this->command?->info('Initial blog content has already been seeded. Nothing changed.');
                return;
            }

            $author = User::query()->whereHas('roles', static fn (Builder $query) => $query
                ->where('name', 'super_admin')->where('guard_name', 'web'))->orderBy('id')->first();
            if ($author === null) {
                throw BlogSeedAuthorMissingException::forInitialContent();
            }

            $content = require database_path('seeders/data/blog.php');
            $categories = $this->seedCategories($content['categories']);
            $tags = $this->seedTags($content['tags']);
            $post = Post::query()->create(['author_id' => $author->id, 'category_id' => $categories['news']]);
            $post->tags()->attach(array_values(array_intersect_key($tags, array_flip(['dashclip-delivery', 'updates', 'getting-started']))));
            foreach ($content['article'] as $locale => $translation) {
                $post->translations()->create([
                    ...$translation,
                    'locale' => $locale,
                    'status' => PostStatusEnum::PUBLISHED,
                    'published_at' => now(),
                    'is_indexable' => true,
                ]);
            }
            $this->command?->info('Initial blog categories, tags and the German/English introduction have been created.');
        });
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
}
