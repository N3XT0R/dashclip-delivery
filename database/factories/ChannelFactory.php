<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Channel;

class ChannelFactory extends EventAwareFactory
{
    protected $model = Channel::class;


    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),   // e.g. "Highway West"
            'creator_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'weight' => $this->faker->numberBetween(1, 10),
            'weekly_quota' => $this->faker->numberBetween(1, 20),
            'is_video_reception_paused' => 0,
        ];
    }

    public function heavy(int $weight = 10): static
    {
        return $this->state(fn() => ['weight' => $weight]);
    }

    public function withQuota(int $quota): static
    {
        return $this->state(fn() => ['weekly_quota' => $quota]);
    }

    public function paused(): static
    {
        return $this->state(fn() => ['is_video_reception_paused' => 1]);
    }
}
