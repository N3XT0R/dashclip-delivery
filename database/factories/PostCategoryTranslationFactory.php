<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PostCategory;
use App\Models\PostCategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostCategoryTranslation>
 */
class PostCategoryTranslationFactory extends Factory
{
    protected $model = PostCategoryTranslation::class;

    public function definition(): array
    {
        return [
            'category_id' => PostCategory::factory(),
            'locale' => 'de',
            'slug' => $this->faker->unique()->slug(),
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
