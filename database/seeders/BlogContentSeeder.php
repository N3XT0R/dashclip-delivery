<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enum\Blog\PostStatusEnum;
use App\Exceptions\Blog\BlogSeedAuthorMissingException;
use App\Models\Post;
use Database\Seeders\Concerns\SeedsBlogContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogContentSeeder extends Seeder
{
    use SeedsBlogContent;

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

            $author = $this->blogAuthor();
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
}
