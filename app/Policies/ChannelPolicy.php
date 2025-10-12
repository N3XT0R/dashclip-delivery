<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Channel;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChannelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Channel');
    }

    public function view(AuthUser $authUser, Channel $channel): bool
    {
        return $authUser->can('View:Channel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Channel');
    }

    public function update(AuthUser $authUser, Channel $channel): bool
    {
        return $authUser->can('Update:Channel');
    }

    public function delete(AuthUser $authUser, Channel $channel): bool
    {
        return $authUser->can('Delete:Channel');
    }

    public function restore(AuthUser $authUser, Channel $channel): bool
    {
        return $authUser->can('Restore:Channel');
    }

    public function forceDelete(AuthUser $authUser, Channel $channel): bool
    {
        return $authUser->can('ForceDelete:Channel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Channel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Channel');
    }

    public function replicate(AuthUser $authUser, Channel $channel): bool
    {
        return $authUser->can('Replicate:Channel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Channel');
    }

}