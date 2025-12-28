<?php

declare(strict_types=1);

namespace App\Notifications\Contracts;

use App\Mail\AbstractLoggedMail;
use App\Models\User;

interface HasToMailContract
{
    public function toMail(User $notifiable): AbstractLoggedMail;
}
