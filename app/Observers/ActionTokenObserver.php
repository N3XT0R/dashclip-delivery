<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\ActionToken\ActionTokenConsumed;
use App\Models\ActionToken;
use Illuminate\Database\Eloquent\Model;

class ActionTokenObserver extends BaseObserver
{
    public function updated(ActionToken|Model $model): void
    {
        if (
            $model->used_at !== null &&
            $model->wasChanged('used_at')) {
            event(new ActionTokenConsumed($model));
        }
    }
}
