<?php

namespace App\Repository;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PasskeyRepository
{
    /** @return Collection<int, Passkey> */
    public function forUser(User $user): Collection
    {
        return $user->passkeys()->orderBy('id')->get();
    }

    public function hasForUser(User $user): bool
    {
        return $user->passkeys()->exists();
    }

    public function rename(User $user, int $id, string $name): void
    {
        $user->passkeys()->findOrFail($id)->update(['name' => $name]);
    }

    public function delete(User $user, int $id): void
    {
        $user->passkeys()->findOrFail($id)->delete();
    }
}
