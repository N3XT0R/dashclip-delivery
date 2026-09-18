<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enum\Blog\PostStatusEnum;
use App\Exceptions\Blog\BlogSeedAuthorMissingException;
use App\Exceptions\Blog\BlogSeedCoverMissingException;
use App\Models\Post;
use Database\Seeders\Concerns\SeedsBlogContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogEditorialQueueSeeder extends Seeder
{
    use SeedsBlogContent;

    private const SEED_NAME = 'scheduled-editorial-queue';

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

            $author = $this->blogAuthor();
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
}
