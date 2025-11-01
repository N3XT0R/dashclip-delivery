<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\MailDirection;
use App\Enum\MailStatus;
use App\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MailLogFactory extends Factory
{
    protected $model = MailLog::class;

    public function definition(): array
    {
        return [
            'direction' => $this->faker->randomElement(MailDirection::cases()),
            'message_id' => '<'.Str::uuid()->toString().'@example.com>',
            'internal_id' => strtoupper(Str::random(10)),
            'to' => $this->faker->safeEmail(),
            'subject' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(MailStatus::cases()),
            'bounced_at' => null,
            'replied_at' => null,
            'meta' => [
                'ip' => $this->faker->ipv4(),
                'agent' => $this->faker->userAgent(),
            ],
        ];
    }

    public function sent(): static
    {
        return $this->state(fn() => ['status' => MailStatus::Sent]);
    }

    public function replied(): static
    {
        return $this->state(fn() => [
            'status' => MailStatus::Replied,
            'replied_at' => now(),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn() => [
            'status' => MailStatus::Bounced,
            'bounced_at' => now(),
        ]);
    }

    public function received(): static
    {
        return $this->state(fn() => ['status' => MailStatus::Received]);
    }

    public function inbound(): static
    {
        return $this->state(fn() => ['direction' => MailDirection::INBOUND]);
    }

    public function outbound(): static
    {
        return $this->state(fn() => ['direction' => MailDirection::OUTBOUND]);
    }
}
