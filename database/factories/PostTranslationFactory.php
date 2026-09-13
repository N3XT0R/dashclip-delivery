<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Blog\PostStatusEnum;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostTranslation>
 */
class PostTranslationFactory extends Factory
{
    protected $model = PostTranslation::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'post_id' => Post::factory(),
            'locale' => 'de',
            'slug' => $this->faker->unique()->slug(),
            'title' => $title,
            'excerpt' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(5, true),
            'status' => PostStatusEnum::DRAFT,
            'published_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(fn(): array => [
            'status' => PostStatusEnum::PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }
}
