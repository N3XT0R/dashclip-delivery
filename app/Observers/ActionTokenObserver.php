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
        if (!$model->wasChanged('used_at')) {
            return;
        }

        if ($model->used_at === null) {
            return;
        }

        if ($model->getOriginal('used_at') !== null) {
            return;
        }

        event(new ActionTokenConsumed($model));
    }
}
