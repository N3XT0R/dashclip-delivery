<?php

namespace App\Models;

use App\Enum\Channel\ApplicationEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'status' => ApplicationEnum::class,
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
}