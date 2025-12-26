<?php

declare(strict_types=1);

namespace App\Notifications\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasToArrayContract
{
    public function toArray(Model $notifiable): array;
}
