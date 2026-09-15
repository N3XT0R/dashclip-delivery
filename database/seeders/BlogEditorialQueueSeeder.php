<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enum\Blog\PostStatusEnum;
use App\Exceptions\Blog\BlogSeedAuthorMissingException;
use App\Exceptions\Blog\BlogSeedCoverMissingException;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BlogEditorialQueueSeeder extends Seeder
{
    private const SEED_NAME = 'scheduled-editorial-queue';

    private const COVER_DIRECTORY = 'blog';

    private const PUBLICATION_HOUR = 9;

    /**
     * Queue one bilingual article per week once, committing the marker and content together.
     * Later runs preserve editorial changes, renamed slugs and deleted records.
     * @throws BlogSeedAuthorMissingException When no existing web administrator can author the articles.
     * @throws BlogSeedCoverMissingException When the shipped cover artwork is not readable.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            if (DB::table('blog_seed_runs')->insertOrIgnore(['name' => self::SEED_NAME, 'completed_at' => now()]) === 0) {
                $this->command?->info('The scheduled blog articles have already been seeded. Nothing changed.');
                return;
            }

            $author = User::query()->whereHas('roles', static fn (Builder $query) => $query
                ->where('name', 'super_admin')->where('guard_name', 'web'))->orderBy('id')->first();
            if ($author === null) {
                throw BlogSeedAuthorMissingException::forEditorialQueue();
            }

            $content = require database_path('seeders/data/blog-editorial-queue.php');
            $categories = $this->seedCategories($content['categories']);
            $tags = $this->seedTags($content['tags']);
            $week = 0;
            foreach ($content['articles'] as $key => $article) {
                $publishedAt = now()->addWeeks(++$week)->setTime(self::PUBLICATION_HOUR, 0);
                $post = Post::query()->create([
                    'author_id' => $author->id,
                    'category_id' => $categories[$article['category']],
                    'image_path' => $this->storeCover($key),
                ]);
                $post->tags()->attach(array_values(array_intersect_key($tags, array_flip($article['tags']))));
                foreach ($article['translations'] as $locale => $translation) {
                    $post->translations()->create([
                        ...$translation,
                        'locale' => $locale,
                        'content' => (string)file_get_contents(database_path('seeders/data/posts/'.$key.'.'.$locale.'.md')),
                        'status' => PostStatusEnum::SCHEDULED,
                        'published_at' => $publishedAt,
                        'is_indexable' => true,
                    ]);
                }
            }
            $this->command?->info('The weekly blog articles have been scheduled in German and English.');
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
        $path = self::COVER_DIRECTORY.'/'.$article.'.webp';
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
