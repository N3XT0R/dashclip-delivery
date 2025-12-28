<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\NotificationTypeEnum;
use App\Models\{Channel, Notification};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 * @deprecated will be removed in next major release
 * @note replaced by mail notifications
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'type' => $this->faker
                ->randomElement(NotificationTypeEnum::cases())
                ->value,
        ];
    }
}
