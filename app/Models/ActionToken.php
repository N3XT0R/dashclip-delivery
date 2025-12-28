<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActionToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'purpose',
        'token_hash',
        'used_at',
        'expires_at',
        'issued_for_user_id',
        'meta',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
