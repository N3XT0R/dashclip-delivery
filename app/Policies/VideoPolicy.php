<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Video;
use Illuminate\Auth\Access\HandlesAuthorization;

class VideoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Video');
    }

    public function view(AuthUser $authUser, Video $video): bool
    {
        return $authUser->can('View:Video');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Video');
    }

    public function update(AuthUser $authUser, Video $video): bool
    {
        return $authUser->can('Update:Video');
    }

    public function delete(AuthUser $authUser, Video $video): bool
    {
        return $authUser->can('Delete:Video');
    }

    public function restore(AuthUser $authUser, Video $video): bool
    {
        return $authUser->can('Restore:Video');
    }

    public function forceDelete(AuthUser $authUser, Video $video): bool
    {
        return $authUser->can('ForceDelete:Video');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Video');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Video');
    }

    public function replicate(AuthUser $authUser, Video $video): bool
    {
        return $authUser->can('Replicate:Video');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Video');
    }

}