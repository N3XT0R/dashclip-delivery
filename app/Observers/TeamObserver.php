<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TeamObserver extends BaseObserver
{
    public function creating(Team|Model $model): void
    {
        if (empty($model->slug)) {
            $model->slug = Str::uuid();
        }
    }
}