<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ChannelConfig extends Model
{
    protected $fillable = [
        'channel_id',
        'key',
        'value',
        'type',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $this->castValue($value, $attributes['type'] ?? 'string'),

            set: fn($value, array $attributes) => $this->serialize($value, $attributes['type'] ?? 'string'),
        );
    }

    private function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int)$value,
            'json' => $value !== null ? json_decode($value, true) : null,
            'datetime' => $value !== null ? Carbon::parse($value) : null,
            'encrypted' => $value !== null ? Crypt::decrypt($value) : null,
            default => $value,
        };
    }

    private function serialize(mixed $value, string $type): string
    {
        return match ($type) {
            'bool', 'int' => (string)$value,
            'json' => json_encode($value),
            'datetime' => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d H:i:s')
                : (string)$value,
            'encrypted' => Crypt::encrypt($value),
            default => (string)$value,
        };
    }
}
