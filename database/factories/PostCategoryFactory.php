<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostCategory>
 */
class PostCategoryFactory extends Factory
{
    protected $model = PostCategory::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(),
            'icon' => null,
        ];
    }
}
