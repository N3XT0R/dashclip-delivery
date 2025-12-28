<?php

declare(strict_types=1);

namespace App\Auth\Abilities\Contracts;

use App\Models\User;

interface AbilityContract
{
    public function check(User $user): bool;
}
