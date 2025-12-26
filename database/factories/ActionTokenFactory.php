<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\TokenPurposeEnum;
use App\Models\ActionToken;
use Illuminate\Support\Str;

class ActionTokenFactory extends EventAwareFactory
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

    public function withPurpose(TokenPurposeEnum $purposeEnum): self
    {
        return $this->state(function () use ($purposeEnum) {
            return [
                'purpose' => $purposeEnum->value,
            ];
        });
    }
}
