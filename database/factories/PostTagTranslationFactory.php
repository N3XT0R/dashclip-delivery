<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PostTag;
use App\Models\PostTagTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostTagTranslation>
 */
class PostTagTranslationFactory extends Factory
{
    protected $model = PostTagTranslation::class;

    public function definition(): array
    {
        return [
            'tag_id' => PostTag::factory(),
            'locale' => 'de',
            'slug' => $this->faker->unique()->slug(),
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
