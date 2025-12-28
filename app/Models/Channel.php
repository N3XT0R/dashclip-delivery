<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Pivots\ChannelUserPivot;
use Illuminate\Database\Eloquent\Builder;
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
        'youtube_name',
        'weight',
        'weekly_quota',
        'is_video_reception_paused',
        'approved_at',
    ];

    protected $casts = [
        'is_video_reception_paused' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function scopeIsActive(Builder $query): Builder
    {
        return $query->where('is_video_reception_paused', false);
    }

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

    /**
     * Generate an approval token for the channel.
     * @return string
     * @todo move to service at next version
     */
    public function getApprovalToken(): string
    {
        return hash('sha256', $this->email . config('app.key'));
    }

    public function getApprovalUrl(): string
    {
        return route('channels.approve', [
            'channel' => $this->getKey(),
            'token' => $this->getApprovalToken(),
        ]);
    }

    public function assignedTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'channel_team')
            ->withPivot(['quota'])
            ->withTimestamps();
    }

    public function channelUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_user')
            ->using(ChannelUserPivot::class)
            ->withPivot(['is_user_verified'])
            ->withTimestamps();
    }

}
