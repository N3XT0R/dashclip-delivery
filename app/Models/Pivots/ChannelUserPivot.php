<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ChannelUserPivot extends Pivot
{
    protected $table = 'channel_user';

    protected $casts = [
        'is_user_verified' => 'bool',
    ];

    protected $fillable = [
        'user_id',
        'channel_id',
        'is_user_verified',
    ];
}
