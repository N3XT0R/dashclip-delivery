<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class Team extends Model
{
    use Notifiable;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
    ];

    protected $casts = [
        'owner_id' => 'int',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The channels assigned to this team.
     * @return BelongsToMany<Channel>
     */
    public function assignedChannels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class)
            ->withPivot(['quota'])
            ->isActive()
            ->withTimestamps();
    }
}
