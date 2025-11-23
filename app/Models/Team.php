<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class Team extends Model
{
    use Notifiable;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
    ];

    protected function scopeIsOwnTeam(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->getKey());
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'team_user'
        )->withTimestamps();
    }

    public function owner(): BelongsToMany
    {
        return $this->users()->wherePivot('is_owner', true);
    }

    public function assignedChannels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class)
            ->withPivot(['quota'])
            ->isActive()
            ->withTimestamps();
    }
}
