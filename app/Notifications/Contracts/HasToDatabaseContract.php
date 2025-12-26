<?php

declare(strict_types=1);

namespace App\Notifications\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasToDatabaseContract
{
    public function toDatabase(Model $notifiable): array;
}
