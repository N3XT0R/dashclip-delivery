<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{

    protected $fillable = [
        'name',
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
}
