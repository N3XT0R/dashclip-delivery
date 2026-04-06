<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Video;
use Illuminate\Database\Eloquent\Model;

class VideoObserver extends BaseObserver
{

    public function deleting(Video|Model $model): bool
    {
        $model->clips()->delete();

        return true;
    }
}
