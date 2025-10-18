<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\MailDirection;
use App\Enum\MailStatus;
use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    protected $fillable = [
        'direction',
        'message_id',
        'transport_id',
        'internal_id',
        'to',
        'subject',
        'status',
        'bounced_at',
        'replied_at',
        'meta',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'direction' => MailDirection::class,
        'meta' => 'array',
        'bounced_at' => 'datetime',
        'replied_at' => 'datetime',
        'status' => MailStatus::class,
    ];
}
