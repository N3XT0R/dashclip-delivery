<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferLinkClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'clicked_at',
        'user_agent',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}