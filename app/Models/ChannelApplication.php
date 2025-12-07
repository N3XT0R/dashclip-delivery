<?php

namespace App\Models;

use App\DTO\Channel\ApplicationMetaDto;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read ApplicationMetaDto $meta
 */
class ChannelApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel_id',
        'status',
        'note',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function meta(): Attribute
    {
        return Attribute::make(
            get: static function ($value) {
                if (is_string($value)) {
                    $value = json_decode($value, true) ?? [];
                }
                return ApplicationMetaDto::fromMetaArray($value ?? []);
            },
        );
    }
}