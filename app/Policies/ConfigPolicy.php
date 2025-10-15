<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Config;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConfigPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Config');
    }

    public function view(AuthUser $authUser, Config $config): bool
    {
        return $authUser->can('View:Config');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Config');
    }

    public function update(AuthUser $authUser, Config $config): bool
    {
        return $authUser->can('Update:Config');
    }

    public function delete(AuthUser $authUser, Config $config): bool
    {
        return $authUser->can('Delete:Config');
    }

    public function restore(AuthUser $authUser, Config $config): bool
    {
        return $authUser->can('Restore:Config');
    }

    public function forceDelete(AuthUser $authUser, Config $config): bool
    {
        return $authUser->can('ForceDelete:Config');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Config');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Config');
    }

    public function replicate(AuthUser $authUser, Config $config): bool
    {
        return $authUser->can('Replicate:Config');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Config');
    }

}