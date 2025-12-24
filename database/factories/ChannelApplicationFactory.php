<?php

namespace Database\Factories;

use App\Enum\Channel\ApplicationEnum;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChannelApplication>
 */
class ChannelApplicationFactory extends Factory
{

    protected $model = ChannelApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel_id' => null,
            'status' => 'pending',
            'note' => $this->faker->sentence(),
            'meta' => [],
        ];
    }

    public function forExistingChannel(?Channel $channel = null): self
    {
        return $this->state(fn() => [
            'channel_id' => $channel ?? Channel::factory(),
        ]);
    }

    public function withMeta(array $meta): self
    {
        return $this->state(fn() => [
            'meta' => $meta,
        ]);
    }

    public function withStatus(ApplicationEnum $status): self
    {
        return $this->state(fn() => [
            'status' => $status->value,
        ]);
    }
}
