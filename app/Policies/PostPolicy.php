<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    /** Authorize viewany through the existing editorial permissions. */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Post');
    }

    /** Authorize view through the existing editorial permissions. */
    public function view(User $user, Post $record): bool
    {
        return $user->can('View:Post');
    }

    /** Authorize create through the existing editorial permissions. */
    public function create(User $user): bool
    {
        return $user->can('Create:Post');
    }

    /** Authorize update through the existing editorial permissions. */
    public function update(User $user, Post $record): bool
    {
        return $user->can('Update:Post');
    }

    /** Authorize delete through the existing editorial permissions. */
    public function delete(User $user, Post $record): bool
    {
        return $user->can('Delete:Post');
    }

    /** Authorize deleteany through the existing editorial permissions. */
    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Post');
    }

    /** Authorize replicate through the existing editorial permissions. */
    public function replicate(User $user, Post $record): bool
    {
        return $user->can('Replicate:Post');
    }

}
