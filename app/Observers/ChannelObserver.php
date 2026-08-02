<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\Channel\ChannelVideoReceptionPaused;
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

    public function updated(Channel|Model $model): void
    {
        if (
            $model->wasChanged('is_video_reception_paused')
            && $model->is_video_reception_paused === true
            && $model->getOriginal('is_video_reception_paused') === false
        ) {
            event(new ChannelVideoReceptionPaused($model));
        }
    }
}
