<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Download;
use Illuminate\Auth\Access\HandlesAuthorization;

class DownloadPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Download');
    }

    public function view(AuthUser $authUser, Download $download): bool
    {
        return $authUser->can('View:Download');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Download');
    }

    public function update(AuthUser $authUser, Download $download): bool
    {
        return $authUser->can('Update:Download');
    }

    public function delete(AuthUser $authUser, Download $download): bool
    {
        return $authUser->can('Delete:Download');
    }

    public function restore(AuthUser $authUser, Download $download): bool
    {
        return $authUser->can('Restore:Download');
    }

    public function forceDelete(AuthUser $authUser, Download $download): bool
    {
        return $authUser->can('ForceDelete:Download');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Download');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Download');
    }

    public function replicate(AuthUser $authUser, Download $download): bool
    {
        return $authUser->can('Replicate:Download');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Download');
    }

}