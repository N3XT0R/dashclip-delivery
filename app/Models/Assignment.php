<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\StatusEnum;
use App\Facades\Cfg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'channel_id',
        'batch_id',
        'status',
        'expires_at',
        'attempts',
        'last_notified_at',
        'download_token',
        'notification_id'
    ];
    protected $casts = [
        'expires_at' => 'datetime',
        'last_notified_at' => 'datetime'
    ];


    /**
     * Scope a query to only include assignments where the associated video has clips by the specified user.
     * @param  Builder  $query
     * @param  User  $user
     * @return Builder
     */
    public function scopeHasUsersClips(Builder $query, User $user): Builder
    {
        return $query->whereHas('video', function (Builder $q) use ($user) {
            $q->whereHas('clips', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        });
    }

    /**
     * Scope a query to only include assignments with specified channel IDs.
     * @param  Builder  $query
     * @param  array  $channelIds
     * @return Builder
     */
    public function scopeHasChannelIds(Builder $query, array $channelIds): Builder
    {
        return $query->whereIn('channel_id', $channelIds);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Set the expiration date for the assignment.
     * @param  int|null  $ttlDays
     * @return void
     */
    public function setExpiresAt(?int $ttlDays = null): void
    {
        if (null === $ttlDays) {
            $ttlDays = Cfg::get('expire_after_days', 'default', 6);
        }

        $expiry = $this->expires_at
            ? min($this->expires_at, now()->addDays($ttlDays)->endOfDay())
            : now()->addDays($ttlDays)->endOfDay();
        $this->setAttribute('expires_at', $expiry);
    }

    /**
     * Mark the assignment as notified.
     * @return void
     */
    public function setNotified(): void
    {
        $this->setAttribute('status', StatusEnum::NOTIFIED->value);
        $this->setAttribute('last_notified_at', now());
    }
}