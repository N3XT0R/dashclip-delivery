<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @deprecated will be removed in next major release
 * @note replaced by mail notifications
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'custom_notifications';

    protected $fillable = [
        'channel_id',
        'type',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
