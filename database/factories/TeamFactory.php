<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug,
            'name' => $this->faker->company(),
            'owner_id' => User::factory(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'owner_id' => $user->getKey(),
        ]);
    }
}