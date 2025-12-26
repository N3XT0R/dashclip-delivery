<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActionToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActionTokenFactory extends Factory
{
    protected $model = ActionToken::class;

    public function definition(): array
    {
        return [
            'purpose' => 'test',
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addHour(),
            'used_at' => null,
            'meta' => null,
        ];
    }
}
