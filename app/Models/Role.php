<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'team_user'
        )->withTimestamps();
    }
}
