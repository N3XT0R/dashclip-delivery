<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        $ext = $this->faker->randomElement(['mp4', 'mov', 'avi', 'mkv']);
        $hash = $this->faker->sha256;

        return [
            'hash' => $hash,
            'ext' => $ext,
            'bytes' => $this->faker->numberBetween(100_000, 2_000_000_000),
            'path' => "videos/{$hash}.{$ext}",
            'meta' => [
                'duration' => $this->faker->numberBetween(5, 1200),
                'width' => $this->faker->randomElement([1280, 1920, 2560]),
                'height' => $this->faker->randomElement([720, 1080, 1440]),
                'codec' => $this->faker->randomElement(['h264', 'hevc', 'mpeg4']),
                'fps' => $this->faker->randomElement([24, 25, 30, 60]),
            ],
            'original_name' => $this->faker->unique()->slug() . ".{$ext}",
            'disk' => 'local',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Video $video) {
            if (!$video->clips()->exists()) {
                Clip::factory()->for($video, 'video')->create();
            }
        });
    }

    public function withoutClips(): static
    {
        return $this->afterCreating(function (Video $video) {
            $video->clips()->delete();
        });
    }

    public function withClips(int $count = 1, ?User $user = null): static
    {
        return $this->afterCreating(function (Video $video) use ($count, $user) {
            Clip::factory()
                ->count($count)
                ->for($video, 'video')
                ->state(fn() => [
                    'user_id' => $user?->getKey() ?? User::factory(),
                ])
                ->create();
        });
    }

    public function onDisk(string $disk): static
    {
        return $this->state(fn() => ['disk' => $disk]);
    }

    public function small(): static
    {
        return $this->state(fn() => ['bytes' => $this->faker->numberBetween(100_000, 5_000_000)]);
    }

    public function large(): static
    {
        return $this->state(fn() => ['bytes' => $this->faker->numberBetween(500_000_000, 2_000_000_000)]);
    }
}
