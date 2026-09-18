<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enum\Blog\PostStatusEnum;
use App\Exceptions\Blog\BlogSeedCoverMissingException;
use App\Models\Post;
use App\Models\PostTranslation;
use Database\Seeders\Concerns\SeedsBlogContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Publish the bilingual news article that explains a release to non-technical users.
 *
 * Content lives in database/seeders/data/releases/<version>.php with <version>.<locale>.md bodies.
 */
class ReleaseNewsSeeder extends Seeder
{
    use SeedsBlogContent;

    private const TAGS = ['dashclip-delivery', 'updates'];

    /**
     * Publish the article once per version, committing the marker and content together.
     * Installations without a web administrator are skipped, so fresh databases still migrate.
     * @throws BlogSeedCoverMissingException When the shipped cover artwork is not readable.
     */
    public function run(string $version): void
    {
        $author = $this->blogAuthor();
        if ($author === null) {
            $this->command?->warn('No web administrator exists to author the '.$version.' release news. Nothing changed.');
            return;
        }

        DB::transaction(function () use ($author, $version): void {
            if (DB::table('blog_seed_runs')->insertOrIgnore(['name' => 'release-news-'.$version, 'completed_at' => now()]) === 0) {
                $this->command?->info('The '.$version.' release news has already been published. Nothing changed.');
                return;
            }

            $release = require database_path('seeders/data/releases/'.$version.'.php');
            $taxonomy = require database_path('seeders/data/blog.php');
            $categories = $this->seedCategories(array_intersect_key($taxonomy['categories'], ['news' => true]));
            $tags = $this->seedTags(array_intersect_key($taxonomy['tags'], array_flip(self::TAGS)));

            $post = Post::query()->create([
                'author_id' => $author->id,
                'category_id' => $categories['news'],
                'image_path' => $this->storeCover($release['key']),
            ]);
            $post->tags()->attach(array_values($tags));
            foreach ($release['translations'] as $locale => $translation) {
                $post->translations()->create([
                    ...$translation,
                    'locale' => $locale,
                    'content' => (string)file_get_contents(database_path('seeders/data/releases/'.$version.'.'.$locale.'.md')),
                    'status' => PostStatusEnum::PUBLISHED,
                    'published_at' => now(),
                    'is_indexable' => true,
                ]);
            }
            $this->command?->info('The '.$version.' release news has been published in German and English.');
        });
    }

    /**
     * Bring already published release news up to date with the shipped article text.
     * Translations edited in the editor since they were created are left untouched.
     */
    public function refreshContent(string $version): void
    {
        $release = require database_path('seeders/data/releases/'.$version.'.php');
        foreach ($release['translations'] as $locale => $translation) {
            $updated = PostTranslation::query()
                ->where('locale', $locale)
                ->where('slug', $translation['slug'])
                ->whereColumn('updated_at', 'created_at')
                ->update(['content' => (string)file_get_contents(database_path('seeders/data/releases/'.$version.'.'.$locale.'.md'))]);
            $this->command?->info('Refreshed '.$updated.' '.$locale.' translation(s) of the '.$version.' release news.');
        }
    }
}
