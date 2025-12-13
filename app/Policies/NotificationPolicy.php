<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * @deprecated will be removed in next major release
 * @note replaced by mail notifications
 */
class NotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Notification');
    }

    public function view(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('View:Notification');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Notification');
    }

    public function update(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('Update:Notification');
    }

    public function delete(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('Delete:Notification');
    }

    public function restore(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('Restore:Notification');
    }

    public function forceDelete(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('ForceDelete:Notification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Notification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Notification');
    }

    public function replicate(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('Replicate:Notification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Notification');
    }

}