<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\BatchTypeEnum;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\OfferLinkClick;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferLinkClickFactory extends Factory
{
    protected $model = OfferLinkClick::class;

    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory()->type(BatchTypeEnum::ASSIGN->value)->create(),
            'channel_id' => Channel::factory()->create(),
            'user_id' => null,
            'clicked_at' => null,
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}