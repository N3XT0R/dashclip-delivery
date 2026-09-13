<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\PostTag;

class PostTagPolicy
{
    /** Authorize viewany through the existing editorial permissions. */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PostTag');
    }

    /** Authorize view through the existing editorial permissions. */
    public function view(User $user, PostTag $record): bool
    {
        return $user->can('View:PostTag');
    }

    /** Authorize create through the existing editorial permissions. */
    public function create(User $user): bool
    {
        return $user->can('Create:PostTag');
    }

    /** Authorize update through the existing editorial permissions. */
    public function update(User $user, PostTag $record): bool
    {
        return $user->can('Update:PostTag');
    }

    /** Authorize delete through the existing editorial permissions. */
    public function delete(User $user, PostTag $record): bool
    {
        return $user->can('Delete:PostTag');
    }

    /** Authorize deleteany through the existing editorial permissions. */
    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:PostTag');
    }

    /** Authorize replicate through the existing editorial permissions. */
    public function replicate(User $user, PostTag $record): bool
    {
        return $user->can('Replicate:PostTag');
    }

}
