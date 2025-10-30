<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'creator_name',
        'email',
        'weight',
        'weekly_quota',
        'is_video_reception_paused',
        'approved_at',
    ];

    protected $casts = [
        'is_video_reception_paused' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function videoBlocks(): HasMany
    {
        return $this->hasMany(ChannelVideoBlock::class);
    }

    public function activeVideoBlocks(): HasMany
    {
        return $this->videoBlocks()->where('until', '>', now());
    }

    public function blockedVideos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'channel_video_blocks')
            ->withPivot('until');
    }

    public function getApprovalToken(): string
    {
        return sha1($this->email.config('app.key'));
    }

    public function getApprovalUrl(): string
    {
        return route('channels.approve', [
            'channel' => $this->getKey(),
            'token' => $this->getApprovalToken(),
        ]);
    }
}