<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserMailConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserMailConfig> */
class UserMailConfigFactory extends Factory
{
    protected $model = UserMailConfig::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => $this->faker->unique()->slug(),
            'value' => $this->faker->boolean(),
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn() => [
            'user_id' => $user->getKey(),
        ]);
    }
}
