<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ConfigCaster;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            get: static fn($value, array $attributes) => ConfigCaster::toPhp(
                $attributes['type'] ?? 'string',
                $value
            ),
            set: static fn($value, array $attributes) => ConfigCaster::toStorage(
                $attributes['type'] ?? 'string',
                $value
            ),
        );
    }
}
