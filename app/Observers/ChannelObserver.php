<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\ChannelCreated;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Model;

class ChannelObserver extends BaseObserver
{
    public function created(Channel|Model $model): void
    {
        parent::created($model);
        event(new ChannelCreated($model));
    }
}
