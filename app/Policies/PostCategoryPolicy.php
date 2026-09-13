<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\PostCategory;

class PostCategoryPolicy
{
    /** Authorize viewany through the existing editorial permissions. */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PostCategory');
    }

    /** Authorize view through the existing editorial permissions. */
    public function view(User $user, PostCategory $record): bool
    {
        return $user->can('View:PostCategory');
    }

    /** Authorize create through the existing editorial permissions. */
    public function create(User $user): bool
    {
        return $user->can('Create:PostCategory');
    }

    /** Authorize update through the existing editorial permissions. */
    public function update(User $user, PostCategory $record): bool
    {
        return $user->can('Update:PostCategory');
    }

    /** Authorize delete through the existing editorial permissions. */
    public function delete(User $user, PostCategory $record): bool
    {
        return $user->can('Delete:PostCategory');
    }

    /** Authorize deleteany through the existing editorial permissions. */
    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:PostCategory');
    }

    /** Authorize replicate through the existing editorial permissions. */
    public function replicate(User $user, PostCategory $record): bool
    {
        return $user->can('Replicate:PostCategory');
    }

}
